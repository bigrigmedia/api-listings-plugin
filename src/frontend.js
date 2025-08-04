// ./src/frontend.js
import { render, Suspense } from '@wordpress/element';
import Block from './api-listings/block';

window.addEventListener( 'DOMContentLoaded', () => {
	console.log( 'frontend.js loaded' );

	const elements = document.querySelectorAll(
		'.wp-block-xpress-api-listings, .my-custom-shortcode'
	);
	elements.forEach(element => {
		const attributes = { ...element.dataset };
		render(
			<Suspense fallback={ <div className="wp-block-placeholder" /> }>
				<Block { ...attributes } />
			</Suspense>,
			element
		);
	});
} );