<?php


$car_catalog = [
    'honda' => [
        'name' => 'Honda',
        'models' => [
            'accord' => [
                'name' => 'Accord',
                'series' => [

       				'series_7' => [
            			
            			'name' => '7. generáció',
            				
                		'years' => [
                    		'2003' => '2003',
                    		'2004' => '2004',
                    		'2005' => '2005',
                   	 		'2006' => '2006',
                    		'2007' => '2007',
                    		'2008' => '2008'
                		],
                		'options' => [
                    		'body' => [
                        		'sedan'  => 'Sedan',
                        		'tourer' => 'Tourer'
                    		],
                    		'engine' => [
                        		'2.0_i-vtec' => '2.0 i-VTEC',
                        		'2.2_i-ctdi' => '2.2 i-CTDi',
                        		'2.4_i-vtec' => '2.4 i-VTEC'
                    		],
                    		'trim' => [
                        		'comfort'   => 'Comfort',
                        		'sport'     => 'Sport',
                        		'se'        => 'Special Edition',
                        		'executive' => 'Executive',
                        		'executive-e' => 'Executive-E',
                        		'types'     => 'Type-S',
                       	 		'euror'     => 'Euro-R'
                    		],
                    		'color' => [
                        		'B-507P'  => 'Arctic Blue Pearl',
                        		'B-536P'  => 'Royal Blue Pearl',
                        		'B-538M'  => 'Blueish Silver Metallic',
                        		'B-92P'   => 'Nighthawk Black Pearl',
                        		'G-516P'  => 'Deep Green Pearl',
                        		'NH-624P' => 'Premium White Pearl',
                        		'NH-658P' => 'Graphite Pearl',
                        		'NH-700M' => 'Alabaster Silver Metallic',
                        		'R-81'    => 'Milano Red',
                        		'YR-562P' => 'Bronze Gray Metallic',
                        		'YR-565P' => 'Dark Mocha Pearl'
                    		]
                		],
                		'vin' => [
   							'rules' => [
        						[
          		  				'position' => 1,
            					'length' => 3,
           						'target' => 'brand',
           							'values' => [
                					'	' => 'honda'
            						]
       							],
        						[
            					'position' => 4,
            					'length' => 3,
            					'target' => 'engine',
            					'model_code' => true,
            						'values' => [
                						'CN1' => '2.2_i-ctdi',
               							'CN2' => '2.2_i-ctdi',
                						'CL7' => '2.0_i-vtec',
                						'CL9' => '2.4_i-vtec'
            						]
        						],
        						[
            					'position' => 7,
            					'length' => 1,
            					'target' => 'body',
           			 				'values' => [
                						'5' => 'sedan',
                						'6' => 'sedan'
            						]
        						],
        						[
    							'position' => 8,
    							'length' => 1,
    							'target' => 'trim',
   									'engine_values' => [
        								'2.2_i-ctdi' => [
            								'2' => 'sport',
           			 						'5' => 'executive'
        								],
        								'2.0_i-vtec' => [
            								'2' => 'comfort',
            								'4' => 'sport',
            								'5' => 'sport',
            								'8' => 'executive'
        								],
        								'2.4_i-vtec' => [
           				 					'0' => 'executive-e',
            								'1' => 'executive-e',
            								'3' => 'sport',
            								'4' => 'types',
            								'7' => 'sport',
            								'8' => 'executive',
            								'9' => 'executive'
        								]
    								]
								],
        						[
            					'position' => 10,
           			 			'length' => 1,
            					'target' => 'production_year',
            						'values' => [
                						'6' => '2006'
            						]
        						]
    						]
						]
					]
				]
            ],
            'crv' => [
                'name' => 'CRV',
                'series' => [

       				'series_4' => [
            			
            			'name' => '4. generáció',
            			
                		'years' => [
                    		'2013' => '2013',
                    		'2014' => '2014',
                    		'2015' => '2015',
                   	 		'2016' => '2016',
                    		'2017' => '2017'
                		],
                		'options' => [
                		
                   	 		'body' => [
                        		'suv'  => 'SUV'
                    		],
                    		'engine' => [
                        		'2.0_i-vtec' => '2.0 i-VTEC', 
                        		'1.6_i-dtec' => '1.6 i-DTEC', 
                        		'2.2_i-dtec' => '2.2 i-DTEC' 
                    		],
                    		'trim' => [
                        		's'   => 'S',
                       		 	'elegance'     => 'Elegance',
                        		'lifestyle'     => 'Lifestyle',
                        		'executive'     => 'Executive'
                    		],
                    		'color' => [
                        		'NH-737M'   => 'Polished Metal Metallic',
                        		'NH-731P'   => 'Crystal Black Pearl',
                        		'YR-580M'   => 'Ionised Bronze Metallic',
                        		'YR-604M'   => 'Golden Brown Metallic',
                        		'YR-578M'   => 'Urban Titanium Metallic',
                        		'NH-700M'   => 'Alabaster Silver Metallic',
                       		 	'NH-830M'   => 'Lunar Silver Metallic',
                        		'R-539P'   => 'Passion Red Pearl',
                        		'R-513'   => 'Rallye Red',
                        		'NH-788P'   => 'White Orchid Pearl',
                        		'B-570M'   => 'Twillight Blue Metallic',
                        		'B-594P'   => 'Deep Ocean Blue'
                    		]
                		],
                		'vin' => [
   							'rules' => [
        						[]
    						]
						]
            		]
            	]
            ]
        ]
    ]
];

$vin_configs = [

    'JHMCN15206C205236' => [

        'name' => 'Accord 2006-08 - CN1 (2.2)',

        'preview' => '/epc/images/kimi.jpg',

        'lista_json' => __DIR__ . '/data/cn1-lista.json',

        'epc_json' => __DIR__ . '/data/cn1-epc.json',
        
        'epc_page' => 'cn1',
        
        'manual_type' => 'cn1',

        'image_dir' => '/epc/images/cn1/',

        'extension' => 'gif',
        
        'epc_enable' => '1',
        
        'manual_enable' => '1',
        

    ],
        'SHSRE5790HU002409' => [

        'name' => 'CRV 2013-17 - RE5 (2.0)',

        'preview' => '/epc/images/crv.jpg',

        'lista_json' => __DIR__ . '/data/re5-lista.json',

        'epc_json' => __DIR__ . '/data/re5-epc.json',
        
        'epc_page' => 're5',
        
        'manual_type' => 're5',

        'image_dir' => '/epc/images/re5/',

        'extension' => 'gif',
        
        'epc_enable' => '0',
        
        'manual_enable' => '0',
        

    ]

];
