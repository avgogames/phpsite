-- Demo catalog. Idempotent: re-running updates existing rows by slug/SKU.

INSERT INTO categories (name, slug, description, sort_order) VALUES
    ('Screws',        'screws',        'Wood, machine and drywall screws in common sizes.', 1),
    ('Nuts & Bolts',  'nuts-bolts',    'Hex bolts, carriage bolts, nuts and washers.',      2),
    ('Nails',         'nails',         'Common, finishing and roofing nails.',              3),
    ('Anchors',       'anchors',       'Wall plugs and anchors for drywall and masonry.',   4),
    ('Hinges & Brackets', 'hinges-brackets', 'Door hinges, shelf brackets and corner braces.', 5),
    ('Hand Tools',    'hand-tools',    'Screwdrivers, hex keys and small hand tools.',      6)
ON CONFLICT (slug) DO UPDATE SET name = EXCLUDED.name, description = EXCLUDED.description, sort_order = EXCLUDED.sort_order;

INSERT INTO products (category_id, sku, name, slug, description, price_cents, stock)
SELECT c.id, p.sku, p.name, p.slug, p.description, p.price_cents, p.stock
FROM (VALUES
    ('screws', 'SCR-WD-4x30-100', 'Wood Screws 4 x 30 mm (100 pack)', 'wood-screws-4x30-100', 'Zinc-plated countersunk Pozidriv wood screws with a sharp point for fast starts in softwood and hardwood.', 549, 120),
    ('screws', 'SCR-WD-5x50-50', 'Wood Screws 5 x 50 mm (50 pack)', 'wood-screws-5x50-50', 'Heavy-duty yellow-passivated wood screws for framing, decking and general construction.', 699, 80),
    ('screws', 'SCR-DW-35-200', 'Drywall Screws 3.5 x 35 mm (200 pack)', 'drywall-screws-35-200', 'Black phosphate bugle-head screws for fixing plasterboard to timber studs.', 849, 60),
    ('screws', 'SCR-MC-M4-50', 'Machine Screws M4 x 20 mm (50 pack)', 'machine-screws-m4-50', 'Stainless steel A2 pan-head Phillips machine screws.', 629, 75),
    ('nuts-bolts', 'BLT-HX-M8x60-10', 'Hex Bolts M8 x 60 mm (10 pack)', 'hex-bolts-m8x60-10', 'Grade 8.8 zinc-plated hex head bolts, partially threaded.', 499, 90),
    ('nuts-bolts', 'NUT-HX-M8-50', 'Hex Nuts M8 (50 pack)', 'hex-nuts-m8-50', 'Zinc-plated steel hex nuts to DIN 934.', 399, 150),
    ('nuts-bolts', 'NUT-NY-M6-50', 'Nylon Lock Nuts M6 (50 pack)', 'nylon-lock-nuts-m6-50', 'Nylon insert lock nuts that resist loosening under vibration.', 459, 100),
    ('nuts-bolts', 'WSH-FL-M8-100', 'Flat Washers M8 (100 pack)', 'flat-washers-m8-100', 'Form A zinc-plated flat washers for spreading load under nuts and bolt heads.', 349, 200),
    ('nuts-bolts', 'BLT-CR-M10x100-4', 'Carriage Bolts M10 x 100 mm (4 pack)', 'carriage-bolts-m10x100-4', 'Galvanised cup-square carriage bolts with nuts and washers, ideal for timber.', 579, 40),
    ('nails', 'NL-CM-50-500G', 'Common Nails 50 mm (500 g)', 'common-nails-50-500g', 'Bright round wire nails for general carpentry.', 429, 110),
    ('nails', 'NL-PN-30-1000', 'Panel Pins 30 mm (1000 pack)', 'panel-pins-30-1000', 'Fine pins for mouldings, beading and light joinery.', 389, 70),
    ('nails', 'NL-RF-40-250G', 'Galvanised Roofing Nails 40 mm (250 g)', 'roofing-nails-40-250g', 'Large flat-head galvanised clout nails for felt and sheet roofing.', 459, 35),
    ('anchors', 'ANC-PL-6-100', 'Wall Plugs 6 mm (100 pack)', 'wall-plugs-6-100', 'Universal nylon wall plugs for brick, block and concrete.', 329, 140),
    ('anchors', 'ANC-DW-SNAP-20', 'Drywall Anchors with Screws (20 pack)', 'drywall-anchors-20', 'Self-drilling zinc anchors for plasterboard, holds up to 25 kg.', 749, 55),
    ('anchors', 'ANC-SL-M8-10', 'Sleeve Anchors M8 x 65 mm (10 pack)', 'sleeve-anchors-m8-10', 'Heavy-duty masonry sleeve anchors for brackets and frames.', 899, 30),
    ('anchors', 'ANC-TG-M5-10', 'Spring Toggle Bolts M5 (10 pack)', 'toggle-bolts-m5-10', 'Spring wing toggles for hollow walls and ceilings.', 649, 4),
    ('hinges-brackets', 'HNG-BT-75-2', 'Butt Hinges 75 mm (pair)', 'butt-hinges-75-pair', 'Satin-chrome steel butt hinges with screws, for interior doors.', 699, 45),
    ('hinges-brackets', 'HNG-CN-35-2', 'Concealed Cabinet Hinges 35 mm (pair)', 'concealed-hinges-35-pair', 'Soft-close clip-on hinges for full-overlay kitchen cabinet doors.', 1099, 25),
    ('hinges-brackets', 'BRK-SH-200-2', 'Shelf Brackets 200 mm (pair)', 'shelf-brackets-200-pair', 'White powder-coated steel shelf brackets, 40 kg per pair.', 549, 60),
    ('hinges-brackets', 'BRK-CB-40-10', 'Corner Braces 40 mm (10 pack)', 'corner-braces-40-10', 'Zinc-plated L-shaped braces for reinforcing joints.', 399, 0),
    ('hand-tools', 'TL-SD-SET-6', 'Screwdriver Set (6 piece)', 'screwdriver-set-6', 'Slotted and Phillips screwdrivers with soft-grip handles and magnetic tips.', 1499, 20),
    ('hand-tools', 'TL-HK-SET-9', 'Hex Key Set (9 piece, metric)', 'hex-key-set-9', 'Ball-end chrome vanadium hex keys 1.5 to 10 mm in a holder.', 899, 35),
    ('hand-tools', 'TL-TM-5M', 'Tape Measure 5 m', 'tape-measure-5m', 'Rubber-cased tape measure with lock and belt clip, metric and imperial.', 799, 50),
    ('hand-tools', 'TL-BT-STUBBY', 'Stubby Ratchet Bit Driver', 'stubby-ratchet-bit-driver', 'Compact ratcheting driver with 12 bits stored in the handle.', 1249, 15)
) AS p(category_slug, sku, name, slug, description, price_cents, stock)
JOIN categories c ON c.slug = p.category_slug
ON CONFLICT (sku) DO UPDATE SET
    name = EXCLUDED.name,
    description = EXCLUDED.description,
    price_cents = EXCLUDED.price_cents,
    category_id = EXCLUDED.category_id;
