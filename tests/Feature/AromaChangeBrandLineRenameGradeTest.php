<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\AromaChangeController;
use App\Models\AromaChange;
use App\Models\MasterProduct;
use App\Models\OptionDetail;
use App\Services\Warehouse\BrandLineRenameSync;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Once;
use Tests\TestCase;

/**
 * Rename Brand Line di Master Option tidak boleh menghapus grade Aroma Switching
 * (Signature 1 < Artisan 2 < Luxo 3): grade menempel di baris option_details, bukan di nama.
 */
class AromaChangeBrandLineRenameGradeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tanpa kolom brand_line_grade: migration yang menambah + mengisinya.
        Schema::create('option_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_option_id');
            $table->unsignedBigInteger('parent_option_id')->nullable();
            $table->string('option_name');
            $table->text('option_description')->nullable();
            $table->string('label')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('brand_line')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('master_options')->insert([
            ['id' => 42, 'name' => 'Brand Lines', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Other Option', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('option_details')->insert([
            ['id' => 209, 'master_option_id' => 42, 'option_name' => 'Signature', 'label' => 'Signature', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 210, 'master_option_id' => 42, 'option_name' => 'Artisan', 'label' => 'Artisan', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 211, 'master_option_id' => 42, 'option_name' => ' luxo ', 'label' => 'Luxo', 'created_at' => now(), 'updated_at' => now()],
            // Nama sama di master option lain tidak boleh ikut diberi grade.
            ['id' => 300, 'master_option_id' => 7, 'option_name' => 'Luxo', 'label' => 'Luxo', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('master_products')->insert([
            ['id' => 1, 'name' => 'Fragrance Ginger Blossom', 'brand_line' => 'Luxo', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Fragrance White Rose', 'brand_line' => 'Artisan', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Fragrance Lemongrass Mix', 'brand_line' => 'Signature', 'created_at' => now(), 'updated_at' => now()],
        ]);

        (require base_path('database/migrations/2026_11_23_000001_add_brand_line_grade_to_option_details_table.php'))->up();
        Once::flush();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('option_details');
        Schema::dropIfExists('master_options');

        parent::tearDown();
    }

    private function decide(int $previousId, int $newId): array
    {
        $controller = new AromaChangeController();
        $method = new \ReflectionMethod($controller, 'resolveGradeApprovalDecision');
        $method->setAccessible(true);

        return $method->invoke($controller, MasterProduct::find($previousId), MasterProduct::find($newId));
    }

    private function rename(int $optionDetailId, string $newName): void
    {
        $detail = OptionDetail::find($optionDetailId);
        $oldName = $detail->option_name;
        $detail->update(['option_name' => $newName]);

        app(BrandLineRenameSync::class)->propagate($detail, $oldName);
    }

    public function test_migration_grades_only_the_brand_lines_option_rows(): void
    {
        $this->assertSame(
            [209 => 1, 210 => 2, 211 => 3, 300 => null],
            DB::table('option_details')->orderBy('id')->pluck('brand_line_grade', 'id')
                ->map(fn ($grade) => $grade === null ? null : (int) $grade)->all()
        );
    }

    public function test_renamed_brand_line_keeps_its_grade(): void
    {
        $this->rename(211, 'Luxe Prestige');

        $this->assertSame('Luxe Prestige', MasterProduct::find(1)->brand_line);
        $this->assertSame(3, AromaChange::brandLineGrade('Luxe Prestige'));
        $this->assertNull(AromaChange::brandLineGrade('Luxo'));

        // Luxe Prestige (dulu Luxo) -> Artisan = turun grade, tetap auto-approve.
        [$status, $isAutoApproved] = $this->decide(1, 2);
        $this->assertSame(AromaChange::STATUS_APPROVED, $status);
        $this->assertTrue($isAutoApproved);

        // Signature -> Luxe Prestige = naik grade, tetap perlu approval.
        [$status, $isAutoApproved] = $this->decide(3, 1);
        $this->assertSame(AromaChange::STATUS_PENDING, $status);
        $this->assertFalse($isAutoApproved);
    }

    public function test_new_brand_line_without_grade_requires_approval(): void
    {
        DB::table('option_details')->insert([
            'id' => 212, 'master_option_id' => 42, 'option_name' => 'Platinum', 'label' => 'Platinum',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('master_products')->insert([
            'id' => 4, 'name' => 'Fragrance Platinum Oud', 'brand_line' => 'Platinum', 'created_at' => now(), 'updated_at' => now(),
        ]);
        Once::flush();

        $this->assertNull(AromaChange::brandLineGrade('Platinum'));

        [$status, $isAutoApproved] = $this->decide(1, 4);
        $this->assertSame(AromaChange::STATUS_PENDING, $status);
        $this->assertFalse($isAutoApproved);
    }

    public function test_grade_map_follows_renamed_brand_line(): void
    {
        $this->rename(209, 'Signature Classic');

        $this->assertSame(
            ['signature classic' => 1, 'artisan' => 2, 'luxo' => 3],
            AromaChange::brandLineGradeMap()
        );
    }
}
