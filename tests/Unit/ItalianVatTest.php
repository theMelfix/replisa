<?php

use App\Support\ItalianVat;

test('accetta una partita iva con checksum valido', function () {
    expect(ItalianVat::isChecksumValid('01809180886'))->toBeTrue();
});

test('normalizza prefisso IT e spazi', function () {
    expect(ItalianVat::isChecksumValid('IT 01809180886'))->toBeTrue()
        ->and(ItalianVat::normalize('IT01809180886'))->toBe('01809180886');
});

test('rifiuta cifra di controllo errata', function () {
    expect(ItalianVat::isChecksumValid('01809180880'))->toBeFalse();
});

test('rifiuta lunghezza diversa da 11 cifre', function () {
    expect(ItalianVat::isChecksumValid('123'))->toBeFalse()
        ->and(ItalianVat::isChecksumValid('abcdefghijk'))->toBeFalse();
});
