<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report');                 // clé du rapport (cf. ReportExportService)
            $table->string('format', 10);             // pdf | excel
            $table->string('label')->nullable();      // libellé lisible pour la notification
            $table->json('params')->nullable();       // filtres du rapport
            $table->string('status')->default('pending')->index(); // pending|processing|completed|failed
            $table->string('file_path')->nullable();  // chemin de stockage (disk local)
            $table->string('file_name')->nullable();  // nom de téléchargement
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};
