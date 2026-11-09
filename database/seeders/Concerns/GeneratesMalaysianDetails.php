<?php

namespace Database\Seeders\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Small helpers that build fictional but well-formed Malaysian contact and
 * identity details, so the seeded records agree with each other (an NRIC that
 * matches the date of birth, an email that matches the name, and so on).
 */
trait GeneratesMalaysianDetails
{
    /**
     * Mobile prefixes in use in Malaysia, written as the digits that follow
     * +60 (so 11 is 011). The 11 range has 8 digits after it, the others 7.
     *
     * @var list<string>
     */
    private const MOBILE_PREFIXES = ['11', '12', '13', '14', '16', '17', '18', '19'];

    /**
     * Place-of-birth codes used in the middle of an NRIC.
     *
     * @var list<string>
     */
    private const NRIC_PLACE_CODES = ['01', '02', '03', '05', '06', '07', '08', '10', '12', '14', '16'];

    /**
     * Banks with their SWIFT code, head office address and the usual length
     * of an account number.
     *
     * @var list<array{name: string, swift: string, address: string, digits: int}>
     */
    private const BANKS = [
        ['name' => 'Maybank', 'swift' => 'MBBEMYKL', 'address' => 'Menara Maybank, 100 Jalan Tun Perak, 50050 Kuala Lumpur', 'digits' => 12],
        ['name' => 'CIMB Bank', 'swift' => 'CIBBMYKL', 'address' => 'Menara CIMB, Jalan Stesen Sentral 2, 50470 Kuala Lumpur', 'digits' => 10],
        ['name' => 'Public Bank', 'swift' => 'PBBEMYKL', 'address' => 'Menara Public Bank, 146 Jalan Ampang, 50450 Kuala Lumpur', 'digits' => 10],
        ['name' => 'RHB Bank', 'swift' => 'RHBBMYKL', 'address' => 'Tower One, RHB Centre, Jalan Tun Razak, 50400 Kuala Lumpur', 'digits' => 14],
        ['name' => 'Hong Leong Bank', 'swift' => 'HLBBMYKL', 'address' => 'Level 8, Wisma Hong Leong, 18 Jalan Perak, 50450 Kuala Lumpur', 'digits' => 11],
    ];

    /**
     * A Malaysian mobile number in E.164 form, e.g. `+60123456789`.
     */
    private function mobileNumber(): string
    {
        $prefix = fake()->randomElement(self::MOBILE_PREFIXES);

        return '+60'.$prefix.fake()->numerify(str_repeat('#', $prefix === '11' ? 8 : 7));
    }

    /**
     * An NRIC number without dashes: date of birth, place code, a serial and a
     * final digit that is odd for men and even for women.
     */
    private function nricFor(CarbonInterface $dateOfBirth, bool $isMale): string
    {
        $lastDigit = $isMale ? fake()->randomElement([1, 3, 5, 7, 9]) : fake()->randomElement([0, 2, 4, 6, 8]);

        return $dateOfBirth->format('ymd')
            .fake()->randomElement(self::NRIC_PLACE_CODES)
            .fake()->numerify('##')
            .$lastDigit;
    }

    /**
     * The local part of an email address built from a name, e.g.
     * "Aiman Hakim bin Roslan" becomes `aiman.hakim.roslan`.
     */
    private function emailLocalPart(string $name): string
    {
        $words = collect(explode(' ', Str::ascii(Str::lower($name))))
            ->reject(fn (string $word): bool => in_array($word, ['bin', 'binti', 'a/l', 'a/p'], true))
            ->map(fn (string $word): string => preg_replace('/[^a-z]/', '', $word) ?? '')
            ->filter()
            ->values();

        if ($words->count() > 3) {
            $words = collect([$words->first(), $words[1], $words->last()]);
        }

        return $words->implode('.');
    }

    /**
     * A bank account number with the length that bank uses.
     *
     * @param  array{digits: int}  $bank
     */
    private function accountNumberAt(array $bank): string
    {
        return fake()->numerify(str_repeat('#', $bank['digits']));
    }

    /**
     * An IPI base number such as `I-123456789-4`.
     */
    private function ipiBaseNumber(): string
    {
        return 'I-'.fake()->numerify('#########').'-'.fake()->randomDigit();
    }

    /**
     * A unique 11-digit IPI name number such as `00123456789`.
     */
    private function ipiNameNumber(): string
    {
        return fake()->unique()->numerify('00#########');
    }

    /**
     * Pick a bank for the given position, cycling through the list.
     *
     * @return array{name: string, swift: string, address: string, digits: int}
     */
    private function bankAt(int $index): array
    {
        return self::BANKS[$index % count(self::BANKS)];
    }
}
