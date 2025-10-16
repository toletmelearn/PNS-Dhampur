<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('isbn')->unique()->nullable();
            $table->string('isbn13')->nullable();
            $table->string('barcode')->unique()->nullable();
            $table->string('accession_number')->unique();
            
            // Author and Publication Information
            $table->string('author');
            $table->string('co_authors')->nullable();
            $table->string('publisher');
            $table->string('publication_place')->nullable();
            $table->year('publication_year')->nullable();
            $table->string('edition')->nullable();
            $table->integer('pages')->nullable();
            $table->string('language')->default('English');
            
            // Classification and Categorization
            $table->string('category'); // Fiction, Non-fiction, Reference, etc.
            $table->string('subject'); // Mathematics, Science, History, etc.
            $table->string('dewey_decimal')->nullable();
            $table->string('call_number')->nullable();
            $table->json('keywords')->nullable();
            $table->json('tags')->nullable();
            
            // Physical Information
            $table->decimal('price', 8, 2)->nullable();
            $table->string('binding_type')->nullable(); // Hardcover, Paperback, etc.
            $table->string('condition')->default('Good'); // New, Good, Fair, Poor
            $table->text('physical_description')->nullable();
            $table->string('location')->nullable(); // Shelf location
            $table->string('section')->nullable(); // Library section
            
            // Acquisition Information
            $table->date('acquisition_date')->nullable();
            $table->string('acquisition_method')->nullable(); // Purchase, Donation, Gift
            $table->string('vendor')->nullable();
            $table->string('bill_number')->nullable();
            $table->date('bill_date')->nullable();
            $table->decimal('acquisition_cost', 8, 2)->nullable();
            
            // Availability and Status
            $table->enum('status', ['available', 'issued', 'reserved', 'lost', 'damaged', 'under_repair', 'withdrawn'])->default('available');
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->integer('issued_copies')->default(0);
            $table->integer('reserved_copies')->default(0);
            $table->integer('damaged_copies')->default(0);
            $table->integer('lost_copies')->default(0);
            
            // Circulation Information
            $table->integer('total_issues')->default(0);
            $table->date('last_issued_date')->nullable();
            $table->boolean('is_reference_only')->default(false);
            $table->integer('max_issue_days')->default(14);
            $table->integer('max_renewals')->default(2);
            $table->decimal('fine_per_day', 5, 2)->default(1.00);
            
            // Digital Information
            $table->boolean('has_ebook')->default(false);
            $table->string('ebook_url')->nullable();
            $table->string('ebook_format')->nullable();
            $table->boolean('has_audiobook')->default(false);
            $table->string('audiobook_url')->nullable();
            
            // Additional Information
            $table->text('description')->nullable();
            $table->text('summary')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('reviews')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('popularity_score')->default(0);
            
            // Administrative Information
            $table->boolean('is_active')->default(true);
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['title', 'author']);
            $table->index(['category', 'subject']);
            $table->index(['status', 'is_active']);
            $table->index(['isbn', 'isbn13']);
            $table->index(['accession_number', 'barcode']);
            $table->index(['location', 'section']);
            $table->index(['acquisition_date', 'acquisition_method']);
            $table->index(['total_issues', 'popularity_score']);
            $table->index(['is_reference_only', 'max_issue_days']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('library_books');
    }
};
