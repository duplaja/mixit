# Mixit.fit: Back End Application

## Built with WP, Gravity Forms

* Integrates Fitbit, Habitica for now
* More Integrations are planned
* Still under construction

Visit at [https://mixit.fit](https://mixit.fit)

## To Set Up Your Own Instance

### Requirements
* WordPress 5.0+
* Gravity Forms
* Fitbit Developer Account
* Fitbit Server Side oAuth2 App (with secret and key)

### Key Points for Setup
* Site options on WP set for mixit_fitbit_client_id and mixit_fitbit_client_secret, matching the Fitbit App (WP CLI works well)
* Import Gravity Forms JSON file
* Set the callback URL on your App to the page with the Fitbit Linking Form
* Change the App ID in the html button in the Fitbit Linking Form
