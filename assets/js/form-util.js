const communities = {
    'highlandsatscotlandyards.com': 215,
    "sunshinemhc.com": 258,
    "lakegriffinisles.com": 221,
    "picciolalanding.com": 237,
    "crystalrivervillage.com": 204,
    "haciendavillagemhc.com": 212,
    "highlandcountryestates.com": 214,
    "lakeviewestatesflorida.com": 223,
    "pineridgemhp.com": 238,
    "shalimarvillagemhp.com": 249,
    "southwayvilla.com": 254,
    "sweetwateroaksmhc.com": 259,
    "timbervillagemhp.com": 262,
    "arcadiavillage.com": 195,
    "imperialoaksmhc.com": 216,
    "magnoliahillmhc.com": 226,
    "pelicanpalmsvillage.com": 234,
    "pelicanpiermhc.com": 235,
    "pelicanpierwest.com": 236,
    "rancherovillage.com": 244,
    "sundancefla.com": 256,
    "villageonthegreensmhc.com": 267,
    "bonnyshores.com": 199,
    "countryvillaestates.com": 202,
    "enchantedlakesmhrv.com": 208,
    "indianwoodmhc.com": 218,
    "lakebluemhp.com": 220,
    "lakepointefl.com": 222,
    "orangeacresmhc.com": 232,
    "pinetreeparkfl.com": 239,
    "quailrunestatesmhc.com": 242,
    "albuquerquemeadows.com": 194,
    "cascadevillagemhp.com": 200,
    "foxfieldmhc.com": 209,
    "longhavenestates.com": 224,
    "meadowlarkmhc.com": 227,
    "pleasantvalleymobileestates.com": 240,
    "silveradopinesmhc.com": 252,
    "terrabuenamhc.com": 260,
    "tradewindscommunity.com": 263,
    "twincedarsmhc.com": 264,
    "westerncarriage.com": 268,
    "westwoodvillagemhc.com": 270,
    "aspenridgemhc.com": 196,
    "collinsaire.com": 201,
    "emeraldacresmhp.com": 207,
    "greenwayterracemhc.com": 211,
    "lagovistamhc.com": 219,
    "millelacsislandresort.com": 229,
    "parkvillagemh.com": 233,
    "poudrevalleymhc.com": 241,
    "sunsetparkmhc.com": 257,
    "vintageacres.com": 255,
    "westernplazamhc.com": 269,
    "baybridgemhc.com": 197,
    "bellwoodplace.com": 198,
    "cranberryrunmhc.com": 203,
    "eagleviewmhc.com": 205,
    "eldoradocourtmhc.com": 206,
    "greathillestates.com": 210,
    "independenceplacemhc.com": 217,
    "meadowledge.com": 228,
    "mogansmhc.com": 230,
    "ontarioshoresrvpark.com": 231,
    "radanteestates.com": 243,
    "redwingmhc.com": 245,
    "sandcastlemhc.com": 246,
    "seacoastresort.com": 247,
    "shadylakesrvresort.com": 248,
    "shawcrestmhc.com": 250,
    "southeastmhc.com": 253,
    "standrock.com": 255,
    "twinlakehomescommunity.com": 265,
    "whitehousecove.com": 271,
};

//Wait for document to be ready
jQuery(document).ready(function($) {
    console.log("Form Util Ready!");

    var apiFormContainer = document.querySelectorAll('.api-form-container');
    apiFormContainer.forEach(function(container) {
    var markupKey = container.getAttribute('data-markup-key');
    var baseUrl = container.getAttribute('data-base-url');
    var communityId = container.getAttribute('data-community-id');
    var markupParam = "?key=" + markupKey;

    var domain = window.location.hostname;
    //Strip the www. from the domain
    domain = domain.replace('www.', '');
    console.log("Domain: " + domain);

    console.log("Community ID: " + communityId);
    console.log("Markup Key: " + markupKey);
    console.log("Base URL: " + baseUrl);

    //Append /thank-you to the domain
    var thankkYouPage =  domain + "/thank-you";
    console.log("Thank You Page: " + thankkYouPage);

    //Fetch the markup from the base URL
    fetch(baseUrl + markupParam)
        .then(response => response.json())
        .then(data => {
            console.log("Data: " + data);
            container.innerHTML = data.markup;

            // Re-execute any <script> tags that came in with the markup,
            // since innerHTML-inserted scripts don't run automatically
            container.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                oldScript.replaceWith(newScript);
            });

            //Target hidden input with name attriubte "community_id" and set the value to the community id or 255 if no community id is found
            $('input[name="community_id"]').val(communities[domain] || communityId || 255);
            $('input[name="successfulCreationReturnURL"]').val(thankkYouPage);
        })
        .catch(error => {
            console.log("Error fetching markup: " + error);
        });
    });

    //Not used
    let queryString = window.location.search;
    let urlParams = new URLSearchParams(queryString);

    let listingId = urlParams.get('listingid');
    let sourcePage = urlParams.get('sourcepage');

    if (listingId) {
        console.log("Listing ID: " + listingId);
    }

    if (sourcePage) {
        console.log("Source Page: " + sourcePage);
    } else {
       sourcePage = "contact";
    }
});