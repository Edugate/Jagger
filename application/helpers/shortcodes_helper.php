<?php

/**
 * returns array if valid notificationtypes as key , and values are descriptive name for lang
 */
function notificationCodes()
{
    $result = array(
        'systemnotifications' => array('desc' => 'system notifications', 'desclang' => 'systemnotifications', 'group' => 'system'),
        'greqisterreq' => array('desc' => 'global idp/sp/federation registration request', 'desclang' => 'greqisterreq', 'group' => 'global'),
        'gfedreqisterreq' => array('desc' => 'global fed registration', 'desclang' => 'gfedreqisterreq', 'group' => 'global'),
        'gidpregisterreq' => array('desc' => 'global IDP registration', 'desclang' => 'gidpregisterreq', 'group' => 'global'),
        'gspregisterreq' => array('desc' => 'global SP registration', 'desclang' => 'gspregisterreq', 'group' => 'global'),
        'gjoinfedreq' => array('desc' => 'global join federation request', 'desclang' => 'gjoinfedreq', 'group' => 'global'),
        'joinfedreq' => array('desc' => 'join federation request', 'desclang' => 'joinfedreq', 'group' => 'special'),
        'gfedmemberschanged' => array('desc' => 'members collection changed for any federation', 'desclang' => 'gfedmemberschanged', 'group' => 'global'),
        'fedmemberschanged' => array('desc' => 'members collection changed for seleceted federation', 'desclang' => 'fedmemberschanged', 'group' => 'special'),
        'grequeststoproviders' => array('desc' => 'All request sent to any provider', 'desclang' => 'grequeststoproviders', 'group' => 'global'),
        'requeststoproviders' => array('desc' => 'Request sent to any defined provider', 'desclang' => 'requeststoproviders', 'group' => 'special')
    );

    return $result;
}

function attrsEntCategory($entype = null)
{
    $result = array();

    if (empty($entype) || strcmp($entype, 'BOTH') == 0)
    {
        $result = array(
            array('name' => 'http://macedir.org/entity-category', 'entype' => array('SP', 'BOTH')),
            array('name' => 'http://macedir.org/entity-category-support', 'entype' => array('IDP', 'BOTH')),
        );
    }
    elseif (strcmp($entype, 'IDP') == 0)
    {
        $result = array(
            array('name' => 'http://macedir.org/entity-category-support', 'entype' => array('IDP', 'BOTH'))
        );
    }
    elseif (strcmp($entype, 'SP') == 0)
    {
        $result = array('name' => 'http://macedir.org/entity-category', 'entype' => array('SP', 'BOTH'));
    }

    return $result;
}

function attrsEntCategoryList($entype = null)
{
    if (!empty($entype))
    {
        if (strcmp($entype, 'IDP') == 0)
        {
            return array('http://macedir.org/entity-category-support');
        }
        elseif (strcmp($entype, 'SP') == 0)
        {
            return array('http://macedir.org/entity-category');
        }
        else
        {
            return array(
                'http://macedir.org/entity-category-support',
                'http://macedir.org/entity-category'
            );
        }
    }
    return array(
        'http://macedir.org/entity-category-support',
        'http://macedir.org/entity-category'
    );
}

function languagesCodes(array $filter = null)
{
    $languages = array(
        'aa' => 'Afar',
        'ab' => 'Abkhaz',
        'ae' => 'Avestan',
        'af' => 'Afrikaans',
        'ak' => 'Akan',
        'am' => 'Amharic',
        'an' => 'Aragonese',
        'ar' => 'Arabic',
        'as' => 'Assamese',
        'av' => 'Avaric',
        'ay' => 'Aymara',
        'az' => 'Azerbaijani',
        'ba' => 'Bashkir',
        'be' => 'Belarusian',
        'bg' => 'Bulgarian',
        'bh' => 'Bihari',
        'bi' => 'Bislama',
        'bm' => 'Bambara',
        'bn' => 'Bengali',
        'bo' => 'Tibetan',
        'br' => 'Breton',
        'bs' => 'Bosnian',
        'ca' => 'Catalan; Valencian',
        'ce' => 'Chechen',
        'ch' => 'Chamorro',
        'co' => 'Corsican',
        'cr' => 'Cree',
        'cs' => 'Czech',
        'cv' => 'Chuvash',
        'cy' => 'Welsh',
        'da' => 'Danish',
        'de' => 'German',
        'dv' => 'Divehi; Dhivehi; Maldivian;',
        'dz' => 'Dzongkha',
        'ee' => 'Ewe',
        'el' => 'Greek, Modern',
        'en' => 'English',
        'eo' => 'Esperanto',
        'es' => 'Spanish; Castilian',
        'et' => 'Estonian',
        'eu' => 'Basque',
        'fa' => 'Persian',
        'ff' => 'Fula; Fulah; Pulaar; Pular',
        'fi' => 'Finnish',
        'fj' => 'Fijian',
        'fo' => 'Faroese',
        'fr' => 'French',
        'fy' => 'Western Frisian',
        'ga' => 'Irish',
        'gd' => 'Scottish Gaelic; Gaelic',
        'gl' => 'Galician',
        'gn' => 'GuaranÃ­',
        'gu' => 'Gujarati',
        'gv' => 'Manx',
        'ha' => 'Hausa',
        'he' => 'Hebrew (modern)',
        'hi' => 'Hindi',
        'ho' => 'Hiri Motu',
        'hr' => 'Croatian',
        'ht' => 'Haitian; Haitian Creole',
        'hu' => 'Hungarian',
        'hy' => 'Armenian',
        'hz' => 'Herero',
        'ia' => 'Interlingua',
        'id' => 'Indonesian',
        'ie' => 'Interlingue',
        'ig' => 'Igbo',
        'ii' => 'Nuosu',
        'ik' => 'Inupiaq',
        'io' => 'Ido',
        'is' => 'Icelandic',
        'it' => 'Italian',
        'iu' => 'Inuktitut',
        'ja' => 'Japanese (ja)',
        'jv' => 'Javanese (jv)',
        'ka' => 'Georgian',
        'kg' => 'Kongo',
        'ki' => 'Kikuyu, Gikuyu',
        'kj' => 'Kwanyama, Kuanyama',
        'kk' => 'Kazakh',
        'kl' => 'Kalaallisut, Greenlandic',
        'km' => 'Khmer',
        'kn' => 'Kannada',
        'ko' => 'Korean',
        'kr' => 'Kanuri',
        'ks' => 'Kashmiri',
        'ku' => 'Kurdish',
        'kv' => 'Komi',
        'kw' => 'Cornish',
        'ky' => 'Kirghiz, Kyrgyz',
        'la' => 'Latin',
        'lb' => 'Luxembourgish, Letzeburgesch',
        'lg' => 'Luganda',
        'li' => 'Limburgish, Limburgan, Limburger',
        'ln' => 'Lingala',
        'lo' => 'Lao',
        'lt' => 'Lithuanian',
        'lu' => 'Luba-Katanga',
        'lv' => 'Latvian',
        'mg' => 'Malagasy',
        'mh' => 'Marshallese',
        'mi' => 'Maori',
        'mk' => 'Macedonian',
        'ml' => 'Malayalam',
        'mn' => 'Mongolian',
        'mr' => 'Marathi',
        'ms' => 'Malay',
        'mt' => 'Maltese',
        'my' => 'Burmese',
        'na' => 'Nauru',
        'nb' => 'Norwegian Bokmål',
        'nd' => 'North Ndebele',
        'ne' => 'Nepali',
        'ng' => 'Ndonga',
        'nl' => 'Dutch',
        'nn' => 'Norwegian Nynorsk',
        'no' => 'Norwegian',
        'nr' => 'South Ndebele',
        'nv' => 'Navajo, Navaho',
        'ny' => 'Chichewa; Chewa; Nyanja',
        'oc' => 'Occitan',
        'oj' => 'Ojibwe, Ojibwa',
        'om' => 'Oromo',
        'or' => 'Oriya',
        'os' => 'Ossetian, Ossetic',
        'pa' => 'Panjabi, Punjabi',
        'pi' => 'Pali',
        'pl' => 'Polish',
        'ps' => 'Pashto, Pushto',
        'pt' => 'Portuguese',
        'qu' => 'Quechua',
        'rm' => 'Romansh',
        'rn' => 'Kirundi',
        'ro' => 'Romanian, Moldavian, Moldovan',
        'ru' => 'Russian',
        'rw' => 'Kinyarwanda',
        'sa' => 'Sanskrit (Saṃskṛtā)',
        'sc' => 'Sardinian',
        'sd' => 'Sindhi',
        'se' => 'Northern Sami',
        'sg' => 'Sango',
        'si' => 'Sinhala, Sinhalese',
        'sk' => 'Slovak',
        'sl' => 'Slovene',
        'sm' => 'Samoan',
        'sn' => 'Shona',
        'so' => 'Somali',
        'sq' => 'Albanian',
        'sr' => 'Serbian',
        'ss' => 'Swati',
        'st' => 'Southern Sotho',
        'su' => 'Sundanese',
        'sv' => 'Swedish',
        'sw' => 'Swahili',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'tg' => 'Tajik',
        'th' => 'Thai',
        'ti' => 'Tigrinya',
        'tk' => 'Turkmen',
        'tl' => 'Tagalog',
        'tn' => 'Tswana',
        'to' => 'Tonga (Tonga Islands)',
        'tr' => 'Turkish',
        'ts' => 'Tsonga',
        'tt' => 'Tatar',
        'tw' => 'Twi',
        'ty' => 'Tahitian',
        'ug' => 'Uighur, Uyghur',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        've' => 'Venda',
        'vi' => 'Vietnamese',
        'vo' => 'Volapük',
        'wa' => 'Walloon',
        'wo' => 'Wolof',
        'xh' => 'Xhosa',
        'yi' => 'Yiddish',
        'yo' => 'Yoruba',
        'za' => 'Zhuang, Chuang',
        'zh' => 'Chinese',
        'zu' => 'Zulu',
        'af' => 'Afrikaans',
        'sq' => 'Albanian',
        'ar-sa' => 'Arabic (Saudi Arabia)',
        'ar-iq' => 'Arabic (Iraq)',
        'ar-eg' => 'Arabic (Egypt)',
        'ar-ly' => 'Arabic (Libya)',
        'ar-dz' => 'Arabic (Algeria)',
        'ar-ma' => 'Arabic (Morocco)',
        'ar-tn' => 'Arabic (Tunisia)',
        'ar-om' => 'Arabic (Oman)',
        'ar-ye' => 'Arabic (Yemen)',
        'ar-sy' => 'Arabic (Syria)',
        'ar-jo' => 'Arabic (Jordan)',
        'ar-lb' => 'Arabic (Lebanon)',
        'ar-kw' => 'Arabic (Kuwait)',
        'ar-ae' => 'Arabic (U.A.E.)',
        'ar-bh' => 'Arabic (Bahrain)',
        'ar-qa' => 'Arabic (Qatar)',
        'eu' => 'Basque',
        'bg' => 'Bulgarian',
        'be' => 'Belarusian',
        'ca' => 'Catalan',
        'zh-tw' => 'Chinese (Taiwan)',
        'zh-cn' => 'Chinese (PRC)',
        'zh-hk' => 'Chinese (Hong Kong SAR)',
        'zh-sg' => 'Chinese (Singapore)',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'nl' => 'Dutch (Standard)',
        'nl-be' => 'Dutch (Belgium)',
        'en' => 'English',
        'en-us' => 'English (US)',
        'en-gb' => 'English (UK)',
        'en-au' => 'English (Australia)',
        'en-ca' => 'English (Canada)',
        'en-nz' => 'English (New Zealand)',
        'en-ie' => 'English (Ireland)',
        'en-za' => 'English (South Africa)',
        'en-jm' => 'English (Jamaica)',
        'en' => 'English',
        'en-bz' => 'English (Belize)',
        'en-tt' => 'English (Trinidad)',
        'et' => 'Estonian',
        'fo' => 'Faeroese',
        'fa' => 'Farsi',
        'fi' => 'Finnish',
        'fr' => 'French (Standard)',
        'fr-be' => 'French (Belgium)',
        'fr-ca' => 'French (Canada)',
        'fr-ch' => 'French (Switzerland)',
        'fr-lu' => 'French (Luxembourg)',
        'gd' => 'Gaelic (Scotland)',
        'ga' => 'Irish',
        'de' => 'German',
        'de-ch' => 'German (Switzerland)',
        'de-at' => 'German (Austria)',
        'de-lu' => 'German (Luxembourg)',
        'de-li' => 'German (Liechtenstein)',
        'el' => 'Greek',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hu' => 'Hungarian',
        'is' => 'Icelandic',
        'id' => 'Indonesian',
        'it' => 'Italian (Standard)',
        'it-ch' => 'Italian (Switzerland)',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'ko' => 'Korean (Johab)',
        'lv' => 'Latvian',
        'lt' => 'Lithuanian',
        'mk' => 'Macedonian (FYROM)',
        'ms' => 'Malaysian',
        'mt' => 'Maltese',
        'no' => 'Norwegian (Bokmal)',
        'no' => 'Norwegian (Nynorsk)',
        'pl' => 'Polish',
        'pt-br' => 'Portuguese (Brazil)',
        'pt' => 'Portuguese (Portugal)',
        'rm' => 'Rhaeto-Romanic',
        'ro' => 'Romanian',
        'ro-mo' => 'Romanian (Republic of Moldova)',
        'ru-mo' => 'Russian (Republic of Moldova)',
        'sz' => 'Sami (Lappish)',
        'sr' => 'Serbian (Cyrillic)',
        'sr' => 'Serbian (Latin)',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'sb' => 'Sorbian',
        'es' => 'Spanish (Spain)',
        'es-mx' => 'Spanish (Mexico)',
        'es-gt' => 'Spanish (Guatemala)',
        'es-cr' => 'Spanish (Costa Rica)',
        'es-pa' => 'Spanish (Panama)',
        'es-do' => 'Spanish (Dominican Republic)',
        'es-ve' => 'Spanish (Venezuela)',
        'es-co' => 'Spanish (Colombia)',
        'es-pe' => 'Spanish (Peru)',
        'es-ar' => 'Spanish (Argentina)',
        'es-ec' => 'Spanish (Ecuador)',
        'es-cl' => 'Spanish (Chile)',
        'es-uy' => 'Spanish (Uruguay)',
        'es-py' => 'Spanish (Paraguay)',
        'es-bo' => 'Spanish (Bolivia)',
        'es-sv' => 'Spanish (El Salvador)',
        'es-hn' => 'Spanish (Honduras)',
        'es-ni' => 'Spanish (Nicaragua)',
        'es-pr' => 'Spanish (Puerto Rico)',
        'sx' => 'Sutu',
        'sv' => 'Swedish',
        'sv-fi' => 'Swedish (Finland)',
        'th' => 'Thai',
        'ts' => 'Tsonga',
        'tn' => 'Tswana',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        've' => 'Venda',
        'vi' => 'Vietnamese',
        'xh' => 'Xhosa',
        'ji' => 'Yiddish',
        'zu' => 'Zulu',
    );
    if ($filter)
    {
        foreach ($filter as $f)
        {
            $result['' . $f . ''] = $languages['' . $f . ''];
        }
        asort($result);
        return $result;
    }

    asort($languages);
    return $languages;
}

?>
