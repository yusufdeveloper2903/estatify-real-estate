<?php
// This file is generated. Do not modify it manually.
return array(
	'mortgage-calculator' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'estatify/mortgage-calculator',
		'version' => '1.0.0',
		'title' => 'Mortgage Calculator',
		'category' => 'estatify',
		'icon' => 'calculator',
		'description' => 'Interactive monthly payment estimate with editable default price, down payment, interest rate and term. Uses the listing price on single property pages.',
		'keywords' => array(
			'real estate',
			'mortgage',
			'loan',
			'calculator',
			'finance'
		),
		'textdomain' => 'estatify',
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'heading' => array(
				'type' => 'string',
				'default' => ''
			),
			'price' => array(
				'type' => 'number',
				'default' => 450000
			),
			'downPayment' => array(
				'type' => 'number',
				'default' => 20
			),
			'rate' => array(
				'type' => 'number'
			),
			'years' => array(
				'type' => 'integer',
				'default' => 30
			),
			'usePropertyPrice' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showBreakdown' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'supports' => array(
			'html' => false,
			'interactivity' => true,
			'align' => array(
				'wide'
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'color' => array(
				'background' => true,
				'text' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'price' => 485000
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'property-details' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'estatify/property-details',
		'version' => '1.0.0',
		'title' => 'Property Details',
		'category' => 'estatify',
		'icon' => 'id-alt',
		'description' => 'Title, price, photo gallery, spec table and features for the current property. Designed for the single property template.',
		'keywords' => array(
			'real estate',
			'gallery',
			'specs',
			'single property'
		),
		'textdomain' => 'estatify',
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'showGallery' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showFeatures' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showHeader' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'supports' => array(
			'html' => false,
			'interactivity' => true,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'property-grid' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'estatify/property-grid',
		'version' => '1.0.0',
		'title' => 'Property Grid',
		'category' => 'estatify',
		'icon' => 'grid-view',
		'description' => 'Listing cards with photo, price, beds, baths and area. Connects to a Property Search block on the same page, or shows a fixed selection.',
		'keywords' => array(
			'real estate',
			'listings',
			'cards',
			'properties'
		),
		'textdomain' => 'estatify',
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'columns' => array(
				'type' => 'integer',
				'default' => 3
			),
			'count' => array(
				'type' => 'integer',
				'default' => 6
			),
			'connectToSearch' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showPagination' => array(
				'type' => 'boolean',
				'default' => true
			),
			'propertyType' => array(
				'type' => 'string',
				'default' => ''
			),
			'location' => array(
				'type' => 'string',
				'default' => ''
			),
			'status' => array(
				'type' => 'string',
				'default' => ''
			),
			'orderBy' => array(
				'type' => 'string',
				'default' => 'newest',
				'enum' => array(
					'newest',
					'price_asc',
					'price_desc',
					'area_desc'
				)
			),
			'excludeCurrent' => array(
				'type' => 'boolean',
				'default' => false
			),
			'inheritArchive' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'html' => false,
			'interactivity' => true,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'count' => 3,
				'connectToSearch' => false
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'property-map' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'estatify/property-map',
		'version' => '1.0.0',
		'title' => 'Property Map',
		'category' => 'estatify',
		'icon' => 'location-alt',
		'description' => 'Leaflet + OpenStreetMap map with a price pin for every listing. Follows the Property Search filters, or shows the current property on single property pages.',
		'keywords' => array(
			'real estate',
			'map',
			'leaflet',
			'openstreetmap',
			'location'
		),
		'textdomain' => 'estatify',
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'height' => array(
				'type' => 'integer',
				'default' => 440
			),
			'zoom' => array(
				'type' => 'integer',
				'default' => 13
			),
			'scrollZoom' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'html' => false,
			'interactivity' => true,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'height' => 300
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'property-search' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'estatify/property-search',
		'version' => '1.0.0',
		'title' => 'Property Search',
		'category' => 'estatify',
		'icon' => 'search',
		'description' => 'Live filters for property listings. Updates the Property Grid and Property Map on the same page without reloading, and keeps the URL in sync.',
		'keywords' => array(
			'real estate',
			'filter',
			'listings',
			'search'
		),
		'textdomain' => 'estatify',
		'attributes' => array(
			'showKeyword' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showStatus' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showPrice' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showSort' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'supports' => array(
			'html' => false,
			'interactivity' => true,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'color' => array(
				'background' => true,
				'text' => true
			)
		),
		'example' => array(
			'attributes' => array(
				
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:./view.js',
		'render' => 'file:./render.php'
	)
);
