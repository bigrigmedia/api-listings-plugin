var apiListingsContainer = document.getElementById("plugin-api-listings-container");
var noListingsFoundContainer = document.getElementById('no-listings-found-container');
var propertyIdOverride = apiListingsContainer.getAttribute('data-property-id-override');

var isSiteHome = apiListingsContainer.getAttribute('data-home') === 'true';
var queryVideoListings = apiListingsContainer.getAttribute('data-query-video') === 'true';
var newOnly = apiListingsContainer.getAttribute('data-new-only') === 'true';
var brokeredOnly = apiListingsContainer.getAttribute('data-brokered-only') === 'true';
var activeOnly = apiListingsContainer.getAttribute('data-active-only') === 'true';
var sosNumber = apiListingsContainer.getAttribute('data-sos-number');
var featuredHomes = apiListingsContainer.getAttribute('data-featured-homes') === 'true';
var slider = apiListingsContainer.getAttribute('data-slider') === 'true';
var whiteNoticeText = apiListingsContainer.getAttribute('data-white-notice-text') === 'true';
var redirectUrl = apiListingsContainer.getAttribute('data-redirect-url');

var listingSosNumber = ''; // Initialize listingSosNumber as all
var listingPurchaseType = ''; // Initialize listingPurchaseType as all
var listingsBedrooms = ''; // Initialize listingsBedrooms as empty
var listingsBathrooms = ''; // Initialize listingsBathrooms as empty
var listingsMinPrice = ''; // Initialize listingsMinPrice as empty
var listingsMaxPrice = ''; // Initialize listingsMaxPrice as empty
var listingSortOrder = 'newest'; // Default sort order is newest to oldest

var allListings = []; // Store all fetched posts
var currentListingCount = 0; // Track the number of posts currently displayed
var listingsPerBatch = 6; // How many posts to show per batch
var listingCount = 72; 

if (slider) {
    listingCount = 6;
    listingsPerBatch = 6;
} else if (isSiteHome) {
    listingCount = 3;
    listingsPerBatch = 3;
}

var propertyId = propertyIdOverride ? propertyIdOverride : api_listings_plugin_settings?.property_id;

// Locate the load more and load less buttons
var loadListingsBtn = document.getElementById("load-listings-btn");

// Locate the loading spinner
var loadingListingsSpinner = document.getElementById("api-listings-loading-spinner");

if (newOnly) {
    listingSosNumber = 'Community Owned - New';
} else if (brokeredOnly) {
    listingSosNumber = 'Brokered';
} else if (sosNumber) {
    listingSosNumber = sosNumber;
}

if(activeOnly) {
    listingPurchaseType = '100';
}

// Add event listeners for filtering and sorting
document.getElementById('listing-sos-number')?.addEventListener('change', function () {
    listingSosNumber = this.value; // Update listingSosNumber with the selected value 
    fetchListings(); // Fetch the posts with the new filter
});

document.getElementById('listing-bedrooms')?.addEventListener('change', function () {
    listingsBedrooms = this.value; // Update listingsBedrooms with the selected value
    jQuery('.listing-filter-pill.bedrooms span').text(this.value + ' BED');
    jQuery('.listing-filter-pill.bedrooms').css('display', 'flex');
    fetchListings(); // Fetch the posts with the new filter
});

document.getElementById('listing-bathrooms')?.addEventListener('change', function () {
    listingsBathrooms = this.value; // Update listingsBathrooms with the selected value
    jQuery('.listing-filter-pill.bathrooms span').text(this.value + ' BATH');
    jQuery('.listing-filter-pill.bathrooms').css('display', 'flex');
    fetchListings(); // Fetch the posts with the new filter
});

// Price filter event listeners - trigger on blur (unfocus) and Enter key
document.getElementById('listing-min-price')?.addEventListener('blur', function () {
    listingsMinPrice = this.value; // Update listingsMinPrice with the selected value
    jQuery('.listing-filter-pill.min-price span').text('$' + this.value + ' MIN');
    jQuery('.listing-filter-pill.min-price').css('display', 'flex');
    fetchListings(); // Fetch the posts with the new filter
});

document.getElementById('listing-min-price')?.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
        listingsMinPrice = this.value; // Update listingsMinPrice with the selected value
        jQuery('.listing-filter-pill.min-price span').text('$' + this.value + ' MIN');
        jQuery('.listing-filter-pill.min-price').css('display', 'flex');
        fetchListings(); // Fetch the posts with the new filter
    }
});

document.getElementById('listing-max-price')?.addEventListener('blur', function () {
    listingsMaxPrice = this.value; // Update listingsMaxPrice with the selected value
    jQuery('.listing-filter-pill.max-price span').text('$' + this.value + ' MAX');
    jQuery('.listing-filter-pill.max-price').css('display', 'flex');
    fetchListings(); // Fetch the posts with the new filter
});

document.getElementById('listing-max-price')?.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
        listingsMaxPrice = this.value; // Update listingsMaxPrice with the selected value
        jQuery('.listing-filter-pill.max-price span').text('$' + this.value + ' MAX');
        jQuery('.listing-filter-pill.max-price').css('display', 'flex');
        fetchListings(); // Fetch the posts with the new filter
    }
});

document.getElementById('listing-sort-order')?.addEventListener('change', function () {
    listingSortOrder = this.value; // Update listingSortOrder with the selected value (newest or oldest)
    fetchListings(); // Fetch the posts with the new sort order
});

//Pill filter event listeners - clear filter and remove pill
document.querySelector('.listing-filter-pill.bedrooms')?.addEventListener('click', function () {
    listingsBedrooms = '';
    jQuery('.listing-filter-pill.bedrooms span').text('');
    jQuery('.listing-filter-pill.bedrooms').css('display', 'none');
    // Reset the bedrooms dropdown
    var bedroomsDropdown = document.getElementById('listing-bedrooms');
    if (bedroomsDropdown) {
        bedroomsDropdown.value = '';
    }
    fetchListings(); // Fetch the posts with the new filter
});

document.querySelector('.listing-filter-pill.bathrooms')?.addEventListener('click', function () {
    listingsBathrooms = '';
    jQuery('.listing-filter-pill.bathrooms span').text('');
    jQuery('.listing-filter-pill.bathrooms').css('display', 'none');
    // Reset the bathrooms dropdown
    var bathroomsDropdown = document.getElementById('listing-bathrooms');
    if (bathroomsDropdown) {
        bathroomsDropdown.value = '';
    }
    fetchListings(); // Fetch the posts with the new filter
});

document.querySelector('.listing-filter-pill.min-price')?.addEventListener('click', function () {
    listingsMinPrice = '';
    jQuery('.listing-filter-pill.min-price span').text('');
    jQuery('.listing-filter-pill.min-price').css('display', 'none');
    // Reset the min price input
    var minPriceInput = document.getElementById('listing-min-price');
    if (minPriceInput) {
        minPriceInput.value = '';
    }
    fetchListings(); // Fetch the posts with the new filter
});

document.querySelector('.listing-filter-pill.max-price')?.addEventListener('click', function () {
    listingsMaxPrice = '';
    jQuery('.listing-filter-pill.max-price span').text('');
    jQuery('.listing-filter-pill.max-price').css('display', 'none');
    // Reset the max price input
    var maxPriceInput = document.getElementById('listing-max-price');
    if (maxPriceInput) {
        maxPriceInput.value = '';
    }
    fetchListings(); // Fetch the posts with the new filter
});

// Clear all filters event listener
document.querySelector('.listing-filter-clear-all')?.addEventListener('click', function () {
    // Reset the bedrooms dropdown
    var bedroomsDropdown = document.getElementById('listing-bedrooms');
    if (bedroomsDropdown) {
        bedroomsDropdown.value = '';
    }
    // Reset the bathrooms dropdown
    var bathroomsDropdown = document.getElementById('listing-bathrooms');
    if (bathroomsDropdown) {
        bathroomsDropdown.value = '';
    }
    // Reset the min price input
    var minPriceInput = document.getElementById('listing-min-price');
    if (minPriceInput) {
        minPriceInput.value = '';
    }
    // Reset the max price input
    var maxPriceInput = document.getElementById('listing-max-price');
    if (maxPriceInput) {
        maxPriceInput.value = '';
    }
    // Reset the bedrooms filter pill
    jQuery('.listing-filter-pill.bedrooms span').text('');
    jQuery('.listing-filter-pill.bedrooms').css('display', 'none');
    // Reset the bathrooms filter pill
    jQuery('.listing-filter-pill.bathrooms span').text('');
    jQuery('.listing-filter-pill.bathrooms').css('display', 'none');
    // Reset the min price filter pill
    jQuery('.listing-filter-pill.min-price span').text('');
    jQuery('.listing-filter-pill.min-price').css('display', 'none');
    // Reset the max price filter pill
    jQuery('.listing-filter-pill.max-price span').text('');
    jQuery('.listing-filter-pill.max-price').css('display', 'none');

    // Reset the filter values
    listingsBedrooms = '';
    listingsBathrooms = '';
    listingsMinPrice = '';
    listingsMaxPrice = '';
    
    fetchListings(); // Fetch the posts with the new filter
});
// Function to show/hide the loading spinner
function showApiLoading() {
    if (loadingListingsSpinner) {
        loadingListingsSpinner.style.display = 'block';
    }
}
function hideApiLoading() {
    if (loadingListingsSpinner) {
        loadingListingsSpinner.style.display = 'none';
    }
}

function updateResultCount(totalPosts) {
    if(totalPosts > 1) {
        jQuery('.listing-result-count').text(totalPosts + ' HOMES FOUND');
    } else {
        jQuery('.listing-result-count').text(totalPosts + ' HOME FOUND');
    }
    jQuery('.listing-result-count').css('display', 'flex');
}

async function checkLength(apiRequest) {
    try {
        const response = await fetch(apiRequest);
        if (response.ok) { // response.ok is true when status is 200-299
            const data = await response.json();
            return data.length;
        }
        throw new Error("Failed to fetch posts");
    } catch (error) {
        console.log(error.message || "Connection Error");
        return 0;
    }
}

// Function to fetch posts
async function fetchListings() {
    // Show loading animation while fetching
    showApiLoading();

    // Reset the post count and clear the container
    currentListingCount = 0;
    apiListingsContainer.innerHTML = "";
    if (noListingsFoundContainer) {
        noListingsFoundContainer.style.display = "none";
    }

    //Create query param for video
    var videoQuery = queryVideoListings ? "&video_tour" : "";
    var featuredHomesQuery = featuredHomes ? "&featured_homes" : "";

    var apiRequest =
        "https://www.legacymhc.com/wp-json/wp/v2/properties?per_page=" + listingCount + "&parent=" + propertyId + "&_embed"
        + "&sos_number=" + encodeURIComponent(listingSosNumber)
        + "&listdate=" + encodeURIComponent(listingSortOrder) 
        + "&bedrooms=" + encodeURIComponent(listingsBedrooms)
        + "&bathrooms=" + encodeURIComponent(listingsBathrooms)
        + "&min_price=" + encodeURIComponent(listingsMinPrice)
        + "&max_price=" + encodeURIComponent(listingsMaxPrice)
        + "&purchase_type=" + encodeURIComponent(listingPurchaseType)
        + videoQuery
        + featuredHomesQuery;

    var apiRequestBrokered =
        "https://www.legacymhc.com/wp-json/wp/v2/properties?per_page=100&parent=" + propertyId + "&_embed"
        + "&sos_number=" + encodeURIComponent("Brokered")

    const brokeredListingsCount = await checkLength(apiRequestBrokered);
    console.log('brokeredListingsCount:', brokeredListingsCount);

    fetch(apiRequest)
        .then(function (response) {
            if (response.ok) { // response.ok is true when status is 200-299
                return response.json();
            }
            throw new Error("Failed to fetch posts");
        })
        .then(function (data) {
            allListings = data; // Store all fetched posts

            if (allListings.length === 0) {
                if (redirectUrl && brokeredListingsCount !== 0) {
                    window.location.href = redirectUrl;
                    return;
                }

                //No longer using innerHTML to display the no listings found message
                //apiListingsContainer.innerHTML = "<div class='no-listings-found' style='color: " + (whiteNoticeText ? "white" : "") + ";'><p>No listings found.</p></div>";
                
                if (noListingsFoundContainer) {
                    noListingsFoundContainer.style.display = "block";
                }
                if (loadListingsBtn) {
                    loadListingsBtn.style.display = "none"; // Hide pagination buttons
                }
                hideApiLoading(); // Hide loading animation
                updateResultCount(allListings.length);
                return;
            }

            // Sort posts by 'sos_number'
            allListings.sort(function (a, b) {
                const order = ["Community Owned - New", "Community Owned - Used", "Brokered"];
                const indexA = order.indexOf(a.acf.sos_number);
                const indexB = order.indexOf(b.acf.sos_number);
                return indexA - indexB;
            });

            displayListings(); // Display the first set of posts
            updateResultCount(allListings.length);
            hideApiLoading(); // Hide loading animation after posts are displayed
            console.log('allListings.length:', allListings.length);
            createListingsSlider(allListings.length);
        })
        .catch(function (error) {
            console.log(error.message || "Connection Error");
            hideApiLoading(); // Hide loading animation on error
        });
}

function createListingsSlider(totalPosts) {
    if (!slider) {
        console.log('Not a slider, skipping slider');
        return;
    }

    jQuery('#plugin-api-listings-container').slick({
        accessibility: true,
        adaptiveHeight: false,
        autoplay: true,
        autoplaySpeed: 5000,
        arrows: true,
        nextArrow: '<div class="next"><img src="https://www.legacymhc.com/app/themes/sage/assets/images/chevron-right.svg" alt="select Next Testimonial"></div>',
        prevArrow: '<div class="prev"><img src="https://www.legacymhc.com/app/themes/sage/assets/images/chevron-left.svg" alt="select Previous Testimonial"></div>',
        dots: false,
        fade: false,
        pauseOnFocus: false,
        pauseOnHover: false,
        speed: 1000,
        slidesToShow: 3,
        infinite: true,
        slidesToScroll: 1,
        centerMode: false,
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                }
            },
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2,
                }
            }
        ]
    })
}

// Function to display posts in batches
function displayListings() {
    var totalPosts = allListings.length;

    // Determine how many posts to display in the current batch
    var end = Math.min(currentListingCount + listingsPerBatch, totalPosts);

    // Loop through the posts and generate the HTML
    for (var i = currentListingCount; i < end; i++) {
        renderHTML(allListings[i]);
    }

    currentListingCount = end; // Update the count of displayed posts

    // Show or hide the Load More button
    if (currentListingCount >= totalPosts) {
        if (loadListingsBtn) {
            loadListingsBtn.style.display = "none";
        }
    } else {
        if (loadListingsBtn) {
            loadListingsBtn.style.display = "inline-block";
        }
    }
}

// Fetch posts on page load
document.addEventListener("DOMContentLoaded", function () {
    fetchListings(); // Load the first set of posts on page load
});

// Load more button event listener
if (loadListingsBtn) {
    loadListingsBtn.onclick = function () {
        showApiLoading(); // Show loading when "Load More" is clicked
        setTimeout(function () {
            displayListings(); // Show more posts when the button is clicked
            hideApiLoading(); // Hide loading animation after posts are displayed
        }, 500); // Adding delay to simulate loading time
    };
}

// Create HTML for each post
function renderHTML(postData) {
    var postDate = postData.acf.listdate;
    var formattedDate = new Date(postDate).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "2-digit",
    });

    var price = postData.acf.price_home.toString().replace(/[^0-9.,]/g, '');
    var price_original = postData.acf.original_list_price.toString().replace(/[^0-9.,]/g, '');
    var formatprice = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(price);

    var removeDecimal = formatprice.slice(0, -3);

    var ourHTMLString = "<div class='unit-listing-wrapper'><div class='listing-purchase-type-" + postData.acf.property_purchase_type + " unit-listing'>";

    var priceDifference = price_original - price;
    priceDifference = priceDifference / 1000;
    priceDifference = Math.round(priceDifference * 10) / 10;

    if (postData._embedded["wp:featuredmedia"]) {
        ourHTMLString +=
            "<div class=unit-image style='background-image: url(" + postData._embedded["wp:featuredmedia"][0].source_url + ")'>";
        if (price_original && price && priceDifference > 0 && 0) {
            ourHTMLString += "<div class='reduced-price'>" + "Price Cut: $" + priceDifference + "k</div>";
        }

        if (postData.acf.property_purchase_type === "500") {
            ourHTMLString += "<div class='listing-banner'>" + "SOLD" + "</div>";
        }

        if (postData.acf.property_purchase_type === "240") {
            ourHTMLString += "<div class='listing-banner'>" + "PENDING" + "</div>";
        }

        ourHTMLString += "</div>";
    } else {
        ourHTMLString += "<div class=unit-image style='background-image: url(https://www.legacymhc.com/app/themes/sage/assets/images/2026-coming-soon.png)'>";
        if (price_original && price && priceDifference > 0 && 0) {
            ourHTMLString += "<div class='reduced-price'>" + "Price Cut: $" + priceDifference + "k</div>";
        }

        if (postData.acf.property_purchase_type === "500") {
            ourHTMLString += "<div class='listing-banner'>" + "SOLD" + "</div>";
        }

        if (postData.acf.property_purchase_type === "240") {
            ourHTMLString += "<div class='listing-banner'>" + "PENDING" + "</div>";
        }

        ourHTMLString += "</div>";
    }

    ourHTMLString += "<div class='unit-data flex-col justify-between'>";
    ourHTMLString += "<a href=/unit-detail?id=" + postData.id + "><strong class='unit-title'>" + postData.title.rendered + "</strong></a>";
    ourHTMLString += "<div>";

    //Start meta-data
    ourHTMLString += "<div class='meta-data'>";
    if (postData.acf.new_construction && 0) {
        ourHTMLString += "<p>" + "New Construction" + "</p>";
    }
    if (postData.acf.construction_type === "400" && 0) {
        ourHTMLString += "<p>" + "Manufactured(Single-Section)" + "</p>";
    }
    if (postData.acf.construction_type === "500" && 0) {
        ourHTMLString += "<p>" + "Mobile home (built prior to 1976)" + "</p>";
    }

    if (postData.acf.bedrooms_home) {
        ourHTMLString += postData.acf.bedrooms_home + " Bedroom";
    }
    if (postData.acf.bathrooms_home) {
        ourHTMLString += (postData.acf.bedrooms_home ? " | " : "") + postData.acf.bathrooms_home + " Bath";
    }


    if (postData.acf.lot_number) {
        if (postData.acf.property_square_footage) {
            ourHTMLString += ((postData.acf.bedrooms_home || postData.acf.bathrooms_home || postData[i].acf.lot_number) ? " | " : "") + postData.acf.property_square_footage + " sq ft";
        } else {
            var length = postData.acf.property_length;
            var width = postData.acf.property_width;
            if (length && width && !isNaN(length) && !isNaN(width)) {
                var squareFootage = length.match(/(\d+)/)[0] * width.match(/(\d+)/)[0];
                ourHTMLString += ((postData.acf.bedrooms_home || postData.acf.bathrooms_home || postData.acf.lot_number) ? " | " : "") + squareFootage + " sq ft";
            }
        }
    }
    
    if(postData.acf.lot_number && 0) {
        ourHTMLString += "<p style='margin-top: 0;'>Site # " + postData.acf.lot_number + "</p>";
    }

    ourHTMLString += "</div>";
    //End meta-data

    ourHTMLString += "<div class=unit-links>";
    ourHTMLString += "<strong class='unit-price'>" + removeDecimal + "</strong>";
    ourHTMLString += "<a href='/unit-detail?id=" + postData.id + "' class='button-api-listing'>" + "Learn More" + "</a>";
    ourHTMLString += "</div>";

    if(0){
        ourHTMLString += "<div class='listing-number'>Listing # " + postData.id + "</div>";
    }

    if(postData.acf.lot_number) {
        ourHTMLString += "<div class='lot-number'>Site # " + postData.acf.lot_number + "</div>";
    }

    if (postData.acf.listdate && 0) {
        ourHTMLString += "<div class='list-date'>" + "Listed: " + formattedDate + "</div>";
    }

    ourHTMLString += "<p class='sos-number'>";
    if (postData.acf.sos_number === "Community Owned - New") {
        ourHTMLString += "CO-N";
    } else if (postData.acf.sos_number === "Community Owned - Used") {
        ourHTMLString += "CO-U";
    } else if (postData.acf.sos_number === "Brokered") {
        ourHTMLString += "BRK";
    }
    ourHTMLString += "</p>";
    ourHTMLString += "</div>";
    ourHTMLString += "</div></div></div>";

    apiListingsContainer.innerHTML += ourHTMLString; // Append the new post to the container
}


