# Audit notes (assessment of the brief)

- Schema: composite unique `daily_distributions(machine_id, user_id, distribution_date)` guarantees one payout per owner per machine per day. Foreign keys restrict deletes on money tables; telemetry cascades with its machine. Indexes cover `(user_id, distribution_date)`, `(assignment_status, tier)`, `hardware_state`, `(machine_id, logged_at)`.
- The brief's 28.5 and 26.3 day paybacks are 28.57 and 26.32 at the nominal share; the worst day of the band is 30 and 28 days.
- Enterprise band: the brief says $360 to $400; the generator stays inside $370 to $390.
- Pools are kept even: enterprise gross is odd ($780.75), so the fee is always an odd number of cents.
- Fast-forward, burst and restock run in a transaction with `lockForUpdate` on the machine row. SQLite (tests) ignores row locks; MySQL honors them.
- Coordinates are approximate public entrances (about 100 m). Check them on the map before presenting.
- The deposit flow tells people to wire money to a peer. It is clearly labelled as fictional everywhere and never collects real details; keep it that way.
