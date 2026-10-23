/**
 * Mortgage maths shared by the calculator view module and its editor preview.
 * Mirrors Estatify\Support\Mortgage::calculate() in PHP.
 */
export const TERMS = [ 10, 15, 20, 25, 30, 35, 40 ];

export function calculate( { price, downPayment, rate, years } ) {
	const safePrice = Math.max( 0, Number( price ) || 0 );
	const downPct = Math.min( 100, Math.max( 0, Number( downPayment ) || 0 ) );
	const annualRate = Math.max( 0, Number( rate ) || 0 );
	const months = Math.max( 1, Math.round( ( Number( years ) || 30 ) * 12 ) );

	const downAmount = ( safePrice * downPct ) / 100;
	const principal = safePrice - downAmount;
	const r = annualRate / 100 / 12;
	const monthly =
		r === 0
			? principal / months
			: ( principal * r * Math.pow( 1 + r, months ) ) /
				( Math.pow( 1 + r, months ) - 1 );
	const totalPaid = monthly * months;
	const interest = Math.max( 0, totalPaid - principal );

	return {
		downAmount,
		principal,
		monthly: Number.isFinite( monthly ) ? monthly : 0,
		interest,
		totalPaid: Number.isFinite( totalPaid ) ? totalPaid : 0,
		principalShare:
			totalPaid > 0 ? Math.round( ( principal / totalPaid ) * 100 ) : 100,
	};
}

export function formatMoney(
	amount,
	{ symbol = '€', position = 'before' } = {},
	locale
) {
	const number = new Intl.NumberFormat(
		locale || document.documentElement.lang || 'en-US',
		{
			maximumFractionDigits: 0,
		}
	).format( Math.round( amount ) );
	return position === 'after'
		? `${ number } ${ symbol }`
		: `${ symbol }${ number }`;
}
