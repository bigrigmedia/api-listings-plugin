import { useEffect, useState } from 'react';

export default function Block( { color, headingSize = 1 } ) {
	const [data, setData] = useState(null);

	useEffect(() => {
		fetch('https://www.legacymhc.com/wp-json/wp/v2/properties?per_page=32&parent=2570&_embed')
			.then(response => response.json())
			.then(data => setData(data))
			.catch(error => console.error('Error fetching data:', error));
	}, []);

	const HeadingTag = `h${ headingSize }`;
	return (
		<div className="wc-interactive-block" style={ { color } }>
			<HeadingTag>Your Magic here</HeadingTag>
			{data && (
				<ul>
					{data.map((item, index) => (
						<li key={index}>{item.title.rendered}</li>
					))}
				</ul>
			)}
		</div>
	);
}