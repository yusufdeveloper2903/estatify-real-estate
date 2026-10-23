/**
 * React versions of the inline icons in includes/Support/Icons.php.
 */

/**
 * Shared 24×24 stroke-icon wrapper.
 *
 * @param {Object}  props          Component props.
 * @param {number}  props.size     Pixel size.
 * @param {Element} props.children SVG shapes.
 */
const Svg = ( { size = 18, children } ) => (
	<svg
		className="estatify-icon"
		width={ size }
		height={ size }
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="1.7"
		strokeLinecap="round"
		strokeLinejoin="round"
		aria-hidden="true"
		focusable="false"
	>
		{ children }
	</svg>
);

export const BedIcon = ( props ) => (
	<Svg { ...props }>
		<path d="M3 18v-6.5A2.5 2.5 0 0 1 5.5 9h13a2.5 2.5 0 0 1 2.5 2.5V18" />
		<path d="M3 15h18" />
		<path d="M5 9V6.5A1.5 1.5 0 0 1 6.5 5h11A1.5 1.5 0 0 1 19 6.5V9" />
		<path d="M9 9V7.5h6V9" />
		<path d="M3 18v1.5M21 18v1.5" />
	</Svg>
);

export const BathIcon = ( props ) => (
	<Svg { ...props }>
		<path d="M4 12h16v2a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-2Z" />
		<path d="M6 12V5.5A1.5 1.5 0 0 1 7.5 4h0A1.5 1.5 0 0 1 9 5.5V6" />
		<path d="M7 19l-1 2M17 19l1 2" />
	</Svg>
);

export const AreaIcon = ( props ) => (
	<Svg { ...props }>
		<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" />
		<rect x="8" y="8" width="8" height="8" rx="1" />
	</Svg>
);

export const PinIcon = ( props ) => (
	<Svg { ...props }>
		<path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21Z" />
		<circle cx="12" cy="9.5" r="2.5" />
	</Svg>
);

export const BuildingIcon = ( props ) => (
	<Svg { ...props }>
		<path d="M4 21V5a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v16" />
		<path d="M15 10h4a1 1 0 0 1 1 1v10" />
		<path d="M8 8h3M8 12h3M8 16h3M3 21h18" />
	</Svg>
);
