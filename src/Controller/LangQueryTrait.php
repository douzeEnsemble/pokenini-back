<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\InvalidLangException;
use Symfony\Component\HttpFoundation\Request;

trait LangQueryTrait
{
    /**
     * The language asked by the "lang" query parameter, null when there is none.
     *
     * @param string[] $locales the enabled locales
     *
     * @throws InvalidLangException when the language is not an enabled locale
     */
    private function getLang(Request $request, array $locales): ?string
    {
        if (!$request->query->has('lang')) {
            return null;
        }

        // An array (lang[]=fr) is refused by Symfony itself with a 400
        $lang = $request->query->getString('lang');

        if (!\in_array($lang, $locales, true)) {
            throw new InvalidLangException('Invalid language');
        }

        return $lang;
    }
}
