<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->id(); // auto-incrementing ID for the upload
            $table->string('path'); // path to the file (stored in public or storage directory)
            $table->string('filename'); // original filename (e.g., 'image.jpg')
            $table->string('mime_type')->nullable(); // MIME type of the file (e.g., 'image/jpeg')
            $table->integer('size')->nullable(); // file size in bytes
            $table->timestamps(); // for created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
