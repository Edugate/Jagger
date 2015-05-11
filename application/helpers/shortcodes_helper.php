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
        'aa' => 'Afar (aa)',
        'ab' => 'Abkhaz (ab)',
        'ae' => 'Avestan (ae)',
        'af' => 'Afrikaans (af)',
        'ak' => 'Akan (ak)',
        'am' => 'Amharic (am)',
        'an' => 'Aragonese (an)',
        'ar' => 'Arabic (ar)',
        'as' => 'Assamese (as)',
        'av' => 'Avaric (av)',
        'ay' => 'Aymara (ay)',
        'az' => 'Azerbaijani (az)',
        'ba' => 'Bashkir (ba)',
        'be' => 'Belarusian (be)',
        'bg' => 'Bulgarian (bg)',
        'bh' => 'Bihari (bh)',
        'bi' => 'Bislama (bi)',
        'bm' => 'Bambara (bm)',
        'bn' => 'Bengali (bn)',
        'bo' => 'Tibetan (bo)',
        'br' => 'Breton (br)',
        'bs' => 'Bosnian (bs)',
        'ca' => 'Catalan; Valencian (ca)',
        'ce' => 'Chechen (ce)',
        'ch' => 'Chamorro (ch)',
        'co' => 'Corsican (co)',
        'cr' => 'Cree (cr)',
        'cs' => 'Czech (cs)',
        'cv' => 'Chuvash (cv)',
        'cy' => 'Welsh (cy)',
        'da' => 'Danish (da)',
        'de' => 'German (de)',
        'dv' => 'Divehi;Dhivehi;Maldivian; (dv)',
        'dz' => 'Dzongkha (dz)',
        'ee' => 'Ewe (ee)',
        'el' => 'Greek, Modern (el)',
        'en' => 'English (en)',
        'eo' => 'Esperanto (eo)',
        'es' => 'Spanish; Castilian (es)',
        'et' => 'Estonian (et)',
        'eu' => 'Basque (eu)',
        'fa' => 'Persian (fa)',
        'ff' => 'Fula; Fulah; Pulaar; Pular (ff)',
        'fi' => 'Finnish (fi)',
        'fj' => 'Fijian (fj)',
        'fo' => 'Faroese (fo)',
        'fr' => 'French (fr)',
        'fy' => 'Western Frisian (fy)',
        'ga' => 'Irish (ga)',
        'gd' => 'Scottish Gaelic; Gaelic (gd)',
        'gl' => 'Galician (gl)',
        'gn' => 'Guarana (gn)',
        'gu' => 'Gujarati (gu)',
        'gv' => 'Manx (gv)',
        'ha' => 'Hausa (ha)',
        'he' => 'Hebrew (modern) (he)',
        'hi' => 'Hindi (hi)',
        'ho' => 'Hiri Motu (ho)',
        'hr' => 'Croatian (hr)',
        'ht' => 'Haitian; Haitian Creole (ht)',
        'hu' => 'Hungarian (hu)',
        'hy' => 'Armenian (hy)',
        'hz' => 'Herero (hz)',
        'ia' => 'Interlingua (ia)',
        'id' => 'Indonesian (id)',
        'ie' => 'Interlingue (ie)',
        'ig' => 'Igbo (ig)',
        'ii' => 'Nuosu (ii)',
        'ik' => 'Inupiaq (ik)',
        'io' => 'Ido (io)',
        'is' => 'Icelandic (is)',
        'it' => 'Italian (it)',
        'iu' => 'Inuktitut (iu)',
        'ja' => 'Japanese (ja)',
        'jv' => 'Javanese (jv)',
        'ka' => 'Georgian (ka)',
        'kg' => 'Kongo (kg)',
        'ki' => 'Kikuyu, Gikuyu (ki)',
        'kj' => 'Kwanyama, Kuanyama (kj)',
        'kk' => 'Kazakh (kk)',
        'kl' => 'Kalaallisut, Greenlandic (kl)',
        'km' => 'Khmer (km)',
        'kn' => 'Kannada (kn)',
        'ko' => 'Korean (ko)',
        'kr' => 'Kanuri (kr)',
        'ks' => 'Kashmiri (ks)',
        'ku' => 'Kurdish (ku)',
        'kv' => 'Komi (kv)',
        'kw' => 'Cornish (kw)',
        'ky' => 'Kirghiz, Kyrgyz (ky)',
        'la' => 'Latin (la)',
        'lb' => 'Luxembourgish, Letzeburgesch (lb)',
        'lg' => 'Luganda (lg)',
        'li' => 'Limburgish, Limburgan, Limburger (li)',
        'ln' => 'Lingala (ln)',
        'lo' => 'Lao (lo)',
        'lt' => 'Lithuanian (lt)',
        'lu' => 'Luba-Katanga (lu)',
        'lv' => 'Latvian (lv)',
        'mg' => 'Malagasy (mg)',
        'mh' => 'Marshallese (mh)',
        'mi' => 'Maori (mi)',
        'mk' => 'Macedonian (mk)',
        'ml' => 'Malayalam (ml)',
        'mn' => 'Mongolian (mn)',
        'mr' => 'Marathi (mr)',
        'ms' => 'Malay (ms)',
        'mt' => 'Maltese (mt)',
        'my' => 'Burmese (my)',
        'na' => 'Nauru (na)',
        'nb' => 'Norwegian Bokmål (nb)',
        'nd' => 'North Ndebele (nd)',
        'ne' => 'Nepali (ne)',
        'ng' => 'Ndonga (ng)',
        'nl' => 'Dutch (nl)',
        'nn' => 'Norwegian Nynorsk (nn)',
        'no' => 'Norwegian (no)',
        'nr' => 'South Ndebele (nr)',
        'nv' => 'Navajo, Navaho (nv)',
        'ny' => 'Chichewa; Chewa; Nyanja (ny)',
        'oc' => 'Occitan (oc)',
        'oj' => 'Ojibwe, Ojibwa (oj)',
        'om' => 'Oromo (om)',
        'or' => 'Oriya (or)',
        'os' => 'Ossetian, Ossetic (os)',
        'pa' => 'Panjabi, Punjabi (pa)',
        'pi' => 'Pali (pi)',
        'pl' => 'Polish (pl)',
        'ps' => 'Pashto, Pushto (ps)',
        'pt' => 'Portuguese (pt)',
        'qu' => 'Quechua (qu)',
        'rm' => 'Romansh (rm)',
        'rn' => 'Kirundi (rn)',
        'ro' => 'Romanian, Moldavian, Moldovan (ro)',
        'ru' => 'Russian (ru)',
        'rw' => 'Kinyarwanda (rw)',
        'sa' => 'Sanskrit (Saṃskṛtā) (sa)',
        'sc' => 'Sardinian (sc)',
        'sd' => 'Sindhi (sd)',
        'se' => 'Northern Sami (se)',
        'sg' => 'Sango (sg)',
        'si' => 'Sinhala, Sinhalese (si)',
        'sk' => 'Slovak (sk)',
        'sl' => 'Slovene (sl)',
        'sm' => 'Samoan (sm)',
        'sn' => 'Shona (sn)',
        'so' => 'Somali (so)',
        'sq' => 'Albanian (sq)',
        'sr' => 'Serbian (sr)',
        'ss' => 'Swati (ss)',
        'st' => 'Southern Sotho (st)',
        'su' => 'Sundanese (su)',
        'sv' => 'Swedish (sv)',
        'sw' => 'Swahili (sw)',
        'ta' => 'Tamil (ta)',
        'te' => 'Telugu (te)',
        'tg' => 'Tajik (tg)',
        'th' => 'Thai (th)',
        'ti' => 'Tigrinya (ti)',
        'tk' => 'Turkmen (tk)',
        'tl' => 'Tagalog (tl)',
        'tn' => 'Tswana (tn)',
        'to' => 'Tonga (Tonga Islands) (to)',
        'tr' => 'Turkish (tr)',
        'ts' => 'Tsonga (ts)',
        'tt' => 'Tatar (tt)',
        'tw' => 'Twi (tw)',
        'ty' => 'Tahitian (ty)',
        'ug' => 'Uighur, Uyghur (ug)',
        'uk' => 'Ukrainian (uk)',
        'ur' => 'Urdu (ur)',
        'uz' => 'Uzbek (uz)',
        've' => 'Venda (ve)',
        'vi' => 'Vietnamese (vi)',
        'vo' => 'Volapük (vo)',
        'wa' => 'Walloon (wa)',
        'wo' => 'Wolof (wo)',
        'xh' => 'Xhosa (xh)',
        'yi' => 'Yiddish (yi)',
        'yo' => 'Yoruba (yo)',
        'za' => 'Zhuang, Chuang (za)',
        'zh' => 'Chinese (zh)',
        'zu' => 'Zulu (zu)',
        'ar-sa' => 'Arabic (Saudi Arabia) (ar-sa)',
        'ar-iq' => 'Arabic (Iraq) (ar-iq)',
        'ar-eg' => 'Arabic (Egypt) (ar-eg)',
        'ar-ly' => 'Arabic (Libya) (ar-ly)',
        'ar-dz' => 'Arabic (Algeria) (ar-dz)',
        'ar-ma' => 'Arabic (Morocco) (ar-ma)',
        'ar-tn' => 'Arabic (Tunisia) (ar-tn)',
        'ar-om' => 'Arabic (Oman) (ar-om)',
        'ar-ye' => 'Arabic (Yemen) (ar-ye)',
        'ar-sy' => 'Arabic (Syria) (ar-sy)',
        'ar-jo' => 'Arabic (Jordan) (ar-jo)',
        'ar-lb' => 'Arabic (Lebanon) (ar-lb)',
        'ar-kw' => 'Arabic (Kuwait) (ar-kw)',
        'ar-ae' => 'Arabic (U.A.E.) (ar-ae)',
        'ar-bh' => 'Arabic (Bahrain) (ar-bh)',
        'ar-qa' => 'Arabic (Qatar) (ar-qa)',
        'zh-tw' => 'Chinese (Taiwan) (zw-tw)',
        'zh-cn' => 'Chinese (PRC) (zh-cn)',
        'zh-hk' => 'Chinese (Hong Kong SAR) (zh-hk)',
        'zh-sg' => 'Chinese (Singapore) (zh-sg)',
        'nl-be' => 'Dutch (Belgium) (nl-be)',
        'en-us' => 'English (US) (en-us)',
        'en-gb' => 'English (UK) (en-gb)',
        'en-au' => 'English (Australia) (en-au)',
        'en-ca' => 'English (Canada) (en-ca)',
        'en-nz' => 'English (New Zealand) (en-nz)',
        'en-ie' => 'English (Ireland) (en-ie)',
        'en-za' => 'English (South Africa) (en-za)',
        'en-jm' => 'English (Jamaica) (en-jm)',
        'en-bz' => 'English (Belize) (en-bz)',
        'en-tt' => 'English (Trinidad) (en-tt)',
        'fr-be' => 'French (Belgium) (fr-be)',
        'fr-ca' => 'French (Canada) (fr-ca)',
        'fr-ch' => 'French (Switzerland) (fr-ch)',
        'fr-lu' => 'French (Luxembourg) (fr-lu)',
        'de-ch' => 'German (Switzerland) (de-ch)',
        'de-at' => 'German (Austria) (de-at)',
        'de-lu' => 'German (Luxembourg) (de-lu)',
        'de-li' => 'German (Liechtenstein) (de-li)',
        'it-ch' => 'Italian (Switzerland) (it-ch)',
        'pt-br' => 'Portuguese (Brazil) (pt-br)',
        'ro-mo' => 'Romanian (Republic of Moldova) (ro-mo)',
        'ru-mo' => 'Russian (Republic of Moldova) (ru-mo)',
        'sz' => 'Sami (Lappish) (sz)',
        'sb' => 'Sorbian (sb)',
        'es-mx' => 'Spanish (Mexico) (es-mx)',
        'es-gt' => 'Spanish (Guatemala) (es-gt)',
        'es-cr' => 'Spanish (Costa Rica) (es-cr)',
        'es-pa' => 'Spanish (Panama) (es-pa)',
        'es-do' => 'Spanish (Dominican Republic) (es-do)',
        'es-ve' => 'Spanish (Venezuela) (es-ve)',
        'es-co' => 'Spanish (Colombia) (es-co)',
        'es-pe' => 'Spanish (Peru) (es-pe)',
        'es-ar' => 'Spanish (Argentina) (es-ar)',
        'es-ec' => 'Spanish (Ecuador) (es-ec)',
        'es-cl' => 'Spanish (Chile) (es-cl)',
        'es-uy' => 'Spanish (Uruguay) (es-uy)',
        'es-py' => 'Spanish (Paraguay) (es-py)',
        'es-bo' => 'Spanish (Bolivia) (es-bo)',
        'es-sv' => 'Spanish (El Salvador) (es-sv)',
        'es-hn' => 'Spanish (Honduras) (es-hn)',
        'es-ni' => 'Spanish (Nicaragua) (es-ni)',
        'es-pr' => 'Spanish (Puerto Rico) (es-pr)',
        'sx' => 'Sutu (sx)',
        'sv-fi' => 'Swedish (Finland) (sv-fi)',
        'ji' => 'Yiddish (ji)',
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
