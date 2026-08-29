<?php

/*
 * EPC központi konfiguráció
 *
 * A CN és CLR közötti minden típusfüggő beállítás
 * egy helyen található.
 *
 * A list.php, search.php és view.php
 * ezt a konfigurációt használja.
 */


/*
 * CN konfiguráció
 */

$configs = [

    'cn1' => [

        /*
         * Megjelenő megnevezés
         */
        'name' => 'Accord 2006-08 - CN1 (2.2) - Sport',
        
		/*
         * Megjelenő kép
         */
        'preview' => '/images/types/honda/accord/series_7/cn1.jpg',

        /*
         * Lista JSON
         *
         * A list.php ezt használja.
         */
        'lista_json' => $_SERVER['DOCUMENT_ROOT'] . '/data/epc/honda/accord/series_7/cn1-lista.json',

        /*
         * EPC JSON
         *
         * A search.php és view.php ezt használja.
         */
        'epc_json' => $_SERVER['DOCUMENT_ROOT'] . '/data/epc/honda/accord/series_7/cn1-epc.json',

        /*
         * Képek webes elérési útja
         */
        'image_dir' => '/images/epc/honda/accord/series_7/cn1/',

        /*
         * Képek fájlkiterjesztése
         */
        'extension' => 'gif',

    ],


    /*
     * CLR konfiguráció
     */

    'clr' => [

        /*
         * Megjelenő megnevezés
         */
        'name' => 'Accord 2006-08 - CL7-R (2.0) - Euro-R',
        
        
		/*
         * Megjelenő kép
         */
        'preview' => '/images/types/honda/accord/series_7/clr.jpg',

        /*
         * Lista JSON
         *
         * A list.php ezt használja.
         */
        'lista_json' => $_SERVER['DOCUMENT_ROOT'] . '/data/epc/honda/accord/series_7/clr-lista.json',

        /*
         * EPC JSON
         *
         * A search.php és view.php ezt használja.
         */
        'epc_json' => $_SERVER['DOCUMENT_ROOT'] . '/data/epc/honda/accord/series_7/clr-epc.json',

        /*
         * Képek webes elérési útja
         */
        'image_dir' => '/images/epc/honda/accord/series_7/clr/',

        /*
         * Képek fájlkiterjesztése
         */
        'extension' => 'png',

    ],

];

