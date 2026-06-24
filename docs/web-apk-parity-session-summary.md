# Rangkuman Sesi — Paritas APK ↔ Web Dashboard (Fase 1–3)

**Tanggal:** 2026-06-09
**Branch:** `develop`
**Commit:** `184a9fa` — "Add web dashboard fallback for technician APK actions (Phases 1-3)"
**Status commit:** ✅ tercommit (belum di-push)

---

## 1. Tujuan

**Paritas penuh** — semua aksi yang bisa dilakukan teknisi di aplikasi APK (`aroma_tablet`) harus bisa juga dilakukan dari **Web Dashboard**, sebagai *fallback* saat APK error/tidak bisa dipakai. Fitur "jaga-jaga" (safety net), bukan pengganti APK.

Dikerjakan **bertahap** berdasarkan risiko menaik.

---

## 2. Arsitektur (terkunci) — B → A

- **Server pemilik state machine**; mobile adalah thin client. **Tidak boleh** membuat state machine kedua yang divergen.
- Setiap aksi paritas web memanggil **satu shared service**: `App\Services\Operational\JobWebCompletionService`.
- Automation hilir (sibling propagation, finalize material, auto-invoice, follow-up) tetap di **satu sumber**: `JobScheduleController::runCompletionAutomation()`.
- Mobile (`Api/Mobile/*`) **tidak disentuh** sampai fase akhir (langkah "A" — ditunda).
- Setiap fase dikunci unit test transisi status.

**Keputusan terkunci lain:**
- Permission reuse: `operational.job-schedules-complete-ba.update,operational.job-schedules.update` (OR). Role Operasional ikut diizinkan.
- Foto = upload file + opsional kamera browser.
- Aroma = **catat DB saja** (`device_snapshot.schedule`), TIDAK push device fisik (`controlDevice()` tidak terpasang di mana pun).

---

## 3. Yang dikerjakan per fase

### Fase 1 — Complete room + foto + Done Job with BA *(risiko rendah)*
- `runCompletionAutomation()` diekstrak dari `update()` (refactor murni; `update()` & `completeWithBa` sama-sama memanggilnya — anti-divergen).
- `JobWebCompletionService::completeRoomWithPhotos()` + `finalizeWithBa()` (mirror mobile `saveRoomCompletionPhotos`/`syncJobPhotoRecord` + `verifyJob`).
- `completeRoomManual` menerima `before_photos[]`/`after_photos[]`.
- Route `complete-with-ba`; UI tombol "Done Job" + modal BA (signature_pad@4.0.0); per-room dialog upload foto before/after.
- BA number idempoten.

### Fase 2 — Lifecycle lokasi *(risiko rendah)*
- `arrivedAtLocation` / `startWork` / `leaveLocation` (mirror mobile :4728 / :2072 / :3922).
- Route `arrived` / `start-work` / `leave-location`; tombol header by status.
- **Gotcha:** `job_team_locations.latitude/longitude` NOT NULL → row lokasi di-SKIP bila lat/lng kosong (operator kantor tanpa GPS); transisi status tetap jalan.

### Fase 3 — Material + unit/serial + aroma *(risiko sedang)*
- `confirmMaterials` (mirror mobile :1340): hanya dari `barang_siap_diambil`; set `material_checked` + `InventoryIssuingService::finalize()`.
- `verifyMaterials` (mirror `MaterialVerificationController`): issuing `processed→sent`, SN → `on_hand`/technician, job terkait → `barang_diambil`. Tolak remove/undone; idempoten bila sudah sent.
- `saveScannedUnit` (mirror :3709, **DB-only**): tulis `job_schedule_units` + aroma di `device_snapshot.schedule`. Same-MAC-beda-room → 409; re-save room sama = update in-place.
- Route `confirm-materials` / `verify-materials` / `save-scanned-unit`; UI tombol "Konfirmasi Material"/"Ambil Barang" + modal "Set Unit & Jadwal Aroma".
- **`validate_serial_number` sengaja TIDAK dibuat**: validator read-only pra-scan; operator web pilih dari SN yang sudah terverifikasi.

---

## 4. File yang diubah (6 file, commit `184a9fa`)

| File | Perubahan |
|---|---|
| `app/Services/Operational/JobWebCompletionService.php` | **BARU** — rumah kanonik semua aksi paritas Fase 1–3 |
| `tests/Feature/JobWebCompletionTest.php` | **BARU** — 7 test, semua hijau |
| `app/Http/Controllers/Operational/JobScheduleController.php` | `runCompletionAutomation` + wrapper web Fase 1–3 |
| `resources/views/operational/job-schedules/show.blade.php` | tombol + modal BA/material/aroma + JS |
| `routes/web.php` | route Fase 1–3 |
| `database/seeders/FixJobSchedulePermissionsSeeder.php` | permission + role Operasional |

---

## 5. Verifikasi

- **Unit/Feature:** `tests/Feature/JobWebCompletionTest.php` — 7 test hijau (gate transisi status; cabang reject return sebelum tulis DB → aman in-memory).
- **Playwright (live local):** verify success path (issuing sent + job `barang_diambil`), re-verify idempoten, aroma save + schedule persist, room-conflict 409, update in-place.
- Job uji: Fase 1 job #4 (`done_job`, BA `JKT-BA/26-06/0001`), Fase 2 job #57, Fase 3 job #225 (`BDG-CSR/26-02/0001`).
- Fixture lokal (set 1 issuing → `processed`) butuh persetujuan user eksplisit (auto-mode rail memblokir mutasi shared record untuk test); diuji **hanya di DB lokal**, row sintetis dibersihkan.

**Catatan pre-existing failing (tidak terkait pekerjaan ini):** `JobSchedulePrintBuildingScriptTest`, `JobScheduleRemoveDetailFallbackScriptTest` (assert string sumber di file yang tidak disentuh).

---

## 6. Fase 4 — DITUNDA (risiko tinggi, menyentuh mobile)

Belum dikerjakan; user condong skip karena kasus jarang & ada workaround manual ERP:
- Partial completion (`cannot_complete_all_rooms`) → follow-up job + MaterialReturn + InventoryReceiving + SerialNumber pending. *(satu-satunya bagian P4 yang benar-benar relevan lapangan)*
- Swap serial number (jarang; workaround edit di Warehouse).
- Adopsi mobile ke shared service (langkah "A") — tanpa nilai user-visible, risiko regresi tinggi.

---

## 7. Langkah berikut (bila diminta)

- **Push** commit `184a9fa` ke remote (belum dilakukan; tunggu instruksi).
- Putuskan apakah Fase 4 dikerjakan atau resmi di-skip.
- Bangun `validate_serial_number` web bila client minta.
