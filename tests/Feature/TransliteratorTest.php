<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Helpers\Transliterator;

class TransliteratorTest extends TestCase
{
    /** @test */
    public function it_transliterates_common_names()
    {
        $this->assertEquals('احمد', Transliterator::toPersian('Ahmad'));
        $this->assertEquals('امان', Transliterator::toPersian('Aman'));
        $this->assertEquals('محمد', Transliterator::toPersian('Mohammad'));
        $this->assertEquals('عبدالله', Transliterator::toPersian('Abdullah'));
    }

    /** @test */
    public function it_handles_empty_names()
    {
        $this->assertEquals('', Transliterator::toPersian(''));
        $this->assertEquals('', Transliterator::toPersian(null));
    }

    /** @test */
    public function it_keeps_persian_names_unchanged()
    {
        $this->assertEquals('احمد', Transliterator::toPersian('احمد'));
        $this->assertEquals('محمد', Transliterator::toPersian('محمد'));
    }

    /** @test */
    public function it_transliterates_character_by_character()
    {
        // For names not in the common list
        $result = Transliterator::toPersian('Kamal');
        $this->assertNotEmpty($result);
    }

    /** @test */
    public function it_handles_batch_transliteration()
    {
        $names = ['Ahmad', 'Aman', 'Mohammad'];
        $result = Transliterator::batchToPersian($names);
        
        $this->assertEquals('احمد', $result[0]);
        $this->assertEquals('امان', $result[1]);
        $this->assertEquals('محمد', $result[2]);
    }
}
