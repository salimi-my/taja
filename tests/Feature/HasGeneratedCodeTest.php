<?php

use App\Concerns\HasGeneratedCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('code_samples', function (Blueprint $table) {
        $table->id();
        $table->string('code')->nullable()->unique();
        $table->timestamps();
    });

    $this->model = new class extends Model
    {
        use HasGeneratedCode;

        protected $table = 'code_samples';

        protected static function generatedCodeColumn(): string
        {
            return 'code';
        }

        protected static function generatedCodePrefix(): string
        {
            return 'TAP';
        }
    };
});

test('a created row gets a zero padded code built from its id', function () {
    $class = $this->model::class;

    $first = $class::create();
    $second = $class::create();

    expect($first->code)->toBe('TAP-000001')
        ->and($second->code)->toBe('TAP-000002')
        ->and($class::find($first->id)->code)->toBe('TAP-000001');
});

test('a code is never reused after a row is deleted', function () {
    $class = $this->model::class;

    $class::create()->delete();
    $next = $class::create();

    expect($next->code)->toBe('TAP-000002');
});

test('formatCode pads the id to 6 digits and does not truncate longer ids', function () {
    expect($this->model::formatCode('WRT', 42))->toBe('WRT-000042')
        ->and($this->model::formatCode('PUB', 1234567))->toBe('PUB-1234567');
});
