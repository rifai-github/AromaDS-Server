<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Finalize kontrak dengan merge: dialog loading harus dibuka SETELAH dialog
 * "Konfirmasi Merger", bukan sebelumnya.
 *
 * SweetAlert2 mempertahankan state loading antar Swal.fire. Kalau
 * Swal.showLoading() sudah aktif saat dialog konfirmasi merger dibuka, tombol
 * "Ya, Gabungkan" tergantikan spinner sehingga hanya tombol Batal yang bisa
 * ditekan — dilaporkan QA 15 Sep 2026 sebagai "loading terus, jadi ga bisa
 * lanjut".
 */
class ContractWizardMergeFinalizeLoadingOrderTest extends TestCase
{
    private function wizardSource(): string
    {
        return file_get_contents(resource_path('views/marketing/contracts/wizard/create.blade.php'));
    }

    public function test_merge_confirmation_comes_before_the_processing_loader(): void
    {
        $view = $this->wizardSource();

        $mergeConfirmPos = strpos($view, "title: 'Konfirmasi Merger'");
        $loaderPos = strpos($view, "text: 'Menyimpan contract...'");

        $this->assertNotFalse($mergeConfirmPos, 'Dialog Konfirmasi Merger tidak ditemukan.');
        $this->assertNotFalse($loaderPos, 'Dialog loading finalize tidak ditemukan.');

        $this->assertLessThan(
            $loaderPos,
            $mergeConfirmPos,
            'Dialog loading dibuka sebelum konfirmasi merger — tombol "Ya, Gabungkan" akan tergantikan spinner.'
        );
    }

    public function test_processing_loader_still_runs_before_the_save_request(): void
    {
        $view = $this->wizardSource();

        $loaderPos = strpos($view, "text: 'Menyimpan contract...'");
        $this->assertNotFalse($loaderPos, 'Dialog loading finalize tidak ditemukan.');

        // Wizard memakai endpoint yang sama untuk Save Draft dan Finalize, jadi yang
        // dicari adalah request pertama SETELAH dialog loading finalize.
        $fetchPos = strpos($view, "fetch('/marketing/contracts/wizard/save'", $loaderPos);

        $this->assertNotFalse(
            $fetchPos,
            'Tidak ada request simpan setelah dialog loading — loading-nya tidak akan pernah ditutup.'
        );
    }
}
