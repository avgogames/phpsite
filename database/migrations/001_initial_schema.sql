-- 001: initial schema for the hardware store.
-- Money is stored as integer cents. Timestamps are timestamptz (UTC).

CREATE TABLE users (
    id            bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email         text        NOT NULL,
    name          text        NOT NULL,
    password_hash text        NOT NULL,
    role          text        NOT NULL DEFAULT 'customer' CHECK (role IN ('customer', 'admin')),
    created_at    timestamptz NOT NULL DEFAULT now(),
    updated_at    timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT users_email_lowercase CHECK (email = lower(email))
);
CREATE UNIQUE INDEX users_email_key ON users (email);

CREATE TABLE categories (
    id          bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name        text NOT NULL,
    slug        text NOT NULL UNIQUE,
    description text NOT NULL DEFAULT '',
    sort_order  int  NOT NULL DEFAULT 0
);

CREATE TABLE products (
    id          bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    category_id bigint      NOT NULL REFERENCES categories (id) ON DELETE RESTRICT,
    sku         text        NOT NULL UNIQUE,
    name        text        NOT NULL,
    slug        text        NOT NULL UNIQUE,
    description text        NOT NULL DEFAULT '',
    price_cents integer     NOT NULL CHECK (price_cents >= 0),
    stock       integer     NOT NULL DEFAULT 0 CHECK (stock >= 0),
    image_url   text,
    is_active   boolean     NOT NULL DEFAULT true,
    created_at  timestamptz NOT NULL DEFAULT now(),
    updated_at  timestamptz NOT NULL DEFAULT now(),
    search      tsvector GENERATED ALWAYS AS (
        setweight(to_tsvector('english', coalesce(name, '')), 'A') ||
        setweight(to_tsvector('english', coalesce(sku, '')), 'A') ||
        setweight(to_tsvector('english', coalesce(description, '')), 'B')
    ) STORED
);
CREATE INDEX products_category_idx ON products (category_id) WHERE is_active;
CREATE INDEX products_search_idx ON products USING gin (search);
CREATE INDEX products_created_idx ON products (created_at DESC);

CREATE TABLE orders (
    id             bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id        bigint      NOT NULL REFERENCES users (id) ON DELETE RESTRICT,
    status         text        NOT NULL DEFAULT 'pending'
                   CHECK (status IN ('pending', 'paid', 'shipped', 'delivered', 'cancelled')),
    subtotal_cents integer     NOT NULL CHECK (subtotal_cents >= 0),
    shipping_cents integer     NOT NULL CHECK (shipping_cents >= 0),
    total_cents    integer     NOT NULL CHECK (total_cents = subtotal_cents + shipping_cents),
    ship_name      text        NOT NULL,
    ship_address   text        NOT NULL,
    ship_city      text        NOT NULL,
    ship_postcode  text        NOT NULL,
    ship_country   text        NOT NULL,
    ship_phone     text        NOT NULL DEFAULT '',
    notes          text        NOT NULL DEFAULT '',
    created_at     timestamptz NOT NULL DEFAULT now(),
    updated_at     timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX orders_user_idx ON orders (user_id, created_at DESC);
CREATE INDEX orders_status_idx ON orders (status, created_at DESC);

-- Line items snapshot name, SKU and price so later catalog edits never change past orders.
CREATE TABLE order_items (
    id               bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id         bigint  NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    product_id       bigint  REFERENCES products (id) ON DELETE SET NULL,
    product_name     text    NOT NULL,
    sku              text    NOT NULL,
    unit_price_cents integer NOT NULL CHECK (unit_price_cents >= 0),
    quantity         integer NOT NULL CHECK (quantity > 0),
    line_total_cents integer NOT NULL CHECK (line_total_cents = unit_price_cents * quantity)
);
CREATE INDEX order_items_order_idx ON order_items (order_id);
CREATE INDEX order_items_product_idx ON order_items (product_id);

CREATE TABLE login_attempts (
    id           bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email        text        NOT NULL,
    ip           inet        NOT NULL,
    attempted_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX login_attempts_email_idx ON login_attempts (email, attempted_at);
CREATE INDEX login_attempts_ip_idx ON login_attempts (ip, attempted_at);

-- Keep updated_at current on every UPDATE.
CREATE FUNCTION touch_updated_at() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    NEW.updated_at := now();
    RETURN NEW;
END;
$$;

CREATE TRIGGER users_touch    BEFORE UPDATE ON users    FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
CREATE TRIGGER products_touch BEFORE UPDATE ON products FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
CREATE TRIGGER orders_touch   BEFORE UPDATE ON orders   FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
