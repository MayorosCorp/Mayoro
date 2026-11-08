# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Mayoro serves two commerce roles in Peru with equal priority when trade-offs appear:

- **Bodega (comprador minorista):** a neighborhood shop owner or buyer restocking wholesale. They browse on desktop and mobile, compare price per bulto, units per package, MOQ and availability, then request a quote from the distributor over WhatsApp. They need to understand the real cost before committing and to avoid quoting an unavailable product.
- **Distribuidor mayorista (proveedor):** a wholesale supplier that publishes its catalog, defines bulto price, units per bulto, MOQ and stock, and receives quote requests. They need to publish and maintain only their own products, see incoming quotes, and keep stock/pricing current.

Visitors without an account are a deliberate third audience: they can explore the catalog and distributor directory, but must register as a bodega before quoting.

## Product Purpose

Mayoro is a B2B wholesale connection platform. It lets bodegas discover distributors and wholesale products with transparent bulto pricing, and lets distributors publish and manage that catalog. A quote is persisted in the system and handed off to WhatsApp so the two businesses can close the deal directly.

Success means a bodega can go from search to a correct WhatsApp quote without confusion, while a distributor can keep an accurate catalog and receive qualified demand. Mayoro does not replace the commercial negotiation between the two businesses.

## Positioning

Mayoro treats the **bulto** as the unit of commerce, not a retail unit with wholesale decoration: price per bulto, units per bulto, MOQ, stock in bultos and quote totals all derive from it. The catalog is public for discovery; the transaction is a direct quote between bodega and distributor over WhatsApp, without a marketplace cart, checkout commission or intermediary payment.

The mechanism a neighboring catalog could not truthfully copy is the complete B2B quote path: public bulto-level discovery → MOQ validation → persisted quote with bulto-based totals → WhatsApp handoff to the actual distributor.

## Operating Context

- **Academic deliverable:** Mayoro is the project of the *Pruebas y Calidad de Software* course at Universidad Continental (Cusco, Peru). Quality, traceability, BDD criteria and automated tests matter alongside the product experience. The master specification lives at `D:\MAYORO\DOCUMENTO_ESPECIFICACION_QA_MAYORO.md`.
- **Local environment:** Laravel 12, PHP 8.4 CLI, Blade, Vite 7 and Tailwind 4; MariaDB via XAMPP for development and SQLite in memory for automated tests.
- **Commercial context:** Peruvian soles (S/), IGV 18%, RUC as 11-digit fiscal identifier, WhatsApp as the normal business channel, WhatsApp numbers in E.164 format.
- **Demo data:** the seeder creates one distributor (*Distribuidora Andina S.A.C.*), one bodega (*Bodega El Comercio E.I.R.L.*) and eight wholesale products across Bebidas, Abarrotes, Lácteos and Limpieza. Product images are empty (`imagen_url = null`).
- **Session reality:** users frequently arrive from a shared product link, authenticate through a modal, and expect to return to the product they were viewing.

## Capabilities and Constraints

**Implemented**
- Public catalog with text search, category filter, pagination and stock states (available, out of stock, paused).
- Public distributor directory and public distributor profile with its active catalog.
- B2B registration and login for `bodega` and `distribuidor`; RBAC middleware returns HTTP 403.
- Distributor product publishing with image upload, bulto price, units per bulto, MOQ and stock.
- Owner-only product edit, pause and reactivate. A distributor can never edit another distributor's product; the server enforces this, not only the interface.
- Quote request for authenticated bodegas with server-side MOQ validation, persisted quote and WhatsApp link generation; email shown as contingency when the phone is unusable.
- Quote totals must be calculated from the **bulto price × quantity of bultos** (with IGV 18% on the subtotal). The current implementation and some tests calculate with the unit price; this is a known defect to correct, not a product choice.

**Required constraints**
- The catalog and distributor directory stay public; quoting requires an authenticated `bodega`.
- Wholesale pricing is per bulto, with units per bulto and a minimum order quantity (MOQ) in bultos.
- The quote closes on WhatsApp; do not introduce cart, checkout, payments, shipping promises or order fulfilment claims in this stage.
- Product ownership is enforced on the server. Editing or pausing a foreign product returns 403.
- Stock and availability are checked on the server when quoting, not only hidden in the interface.
- Do not present placeholder modules as working features.

**Known placeholders / undecided**
- Orders, Inventory and Reports have routes and screens but no real data model or behavior yet.
- The Premium inventory/recommendation module is specified but not implemented.
- Quote lifecycle states are not finalized. Current states are `pendiente`, `enviada`, `aceptada`, `rechazada`; the product needs an honest state for "link generated" that does not claim WhatsApp delivery.
- Multi-product quote lists, price tiers by volume, delivery terms and payment terms are out of scope until separately specified.

**Terminology**
- `bodega`: buyer / neighborhood retailer.
- `distribuidor` or `proveedor`: wholesale supplier. In the interface "proveedor" is used as a synonym for the distributor role.
- `bulto`: case, fardo, sack or package that contains N units.
- `MOQ`: minimum quantity of bultos per quote.
- `cotización`: persisted quote request, not a binding order.

## Brand Commitments

- The product name is **Mayoro**. No logo, brand manual or official visual identity exists; only the name is confirmed.
- The interface language is Spanish (Peru), direct and commercial, addressed to shop owners and distributors as businesses.
- Tone must stay honest: no fabricated testimonials, customer counts, verification badges, delivery promises, payment options or discount claims.

## Evidence on Hand

- Runnable Laravel application at `D:\MAYORO\Mayoro` with Blade views and a seeded demo dataset.
- Master requirements and QA specification: `D:\MAYORO\DOCUMENTO_ESPECIFICACION_QA_MAYORO.md`.
- Installation, test accounts and quality pipeline: `D:\MAYORO\Mayoro\README.md`.
- Automated test suite under `D:\MAYORO\Mayoro\tests`; the last full local run passed 138 tests and 435 assertions.
- UX/UI audit and change communication: `D:\MAYORO\AUDITORIA_UX_FALABELLA_MAYORO_2026_11_02.md` and `D:\MAYORO\COMUNICADO_PLAN_CAMBIOS_MAYORO_2026_11_02.md`.

**Absent evidence future work must not fabricate:** real customers, real distributor logos, product photography, testimonials, press, delivery times, return policies, payment methods, certifications, or verification of businesses. There is no production deployment or real usage data yet.

## Product Principles

1. **The bulto is the unit of truth.** Price, stock, MOQ and quote totals are expressed and calculated in bultos; the unit price is reference information, never the basis of the transaction.
2. **Quote, don't pretend to sell.** Mayoro registers a quote and hands it to WhatsApp. It must never imply a confirmed order, payment or delivery.
3. **Both sides deserve a complete workflow.** Buyer discovery and distributor catalog management are equally important; neither is sacrificed to polish the other.
4. **Public discovery, authenticated commitment.** Anyone can explore; only a registered bodega can quote, and only the owner can manage a product.
5. **Honest interface.** Show only capabilities backed by real data and server rules. Hide or label unfinished modules instead of offering dead ends.

## Accessibility & Inclusion

No formal accessibility standard is required. The project adopts good practices without claiming conformance: keyboard-operable flows, visible focus, sufficient contrast, labeled form fields, useful image alternatives and dialogs that can be closed with Escape. These are quality expectations, not a certified WCAG level.
