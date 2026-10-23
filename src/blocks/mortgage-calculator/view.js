/**
 * Mortgage Calculator front end (local context per block instance).
 */
import { store, getContext } from '@wordpress/interactivity';
import { calculate, formatMoney } from '../../shared/mortgage';

const FIELDS = [ 'price', 'downPayment', 'rate', 'years' ];

store( 'estatify/calculator', {
	actions: {
		update( event ) {
			const context = getContext();
			const { name, value } = event.target;
			if ( ! FIELDS.includes( name ) ) {
				return;
			}
			context[ name ] = value === '' ? 0 : Number( value );

			const result = calculate( context );
			const money = ( amount ) => formatMoney( amount, context );

			context.monthlyLabel = money( result.monthly );
			context.downLabel = money( result.downAmount );
			context.principalLabel = money( result.principal );
			context.interestLabel = money( result.interest );
			context.totalLabel = money( result.totalPaid );
			context.downPctLabel = `${ Math.round( context.downPayment ) }%`;
			context.principalStyle = `width:${ result.principalShare }%`;
		},
	},
} );
