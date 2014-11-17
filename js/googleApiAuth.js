// Upon loading, the Google APIs JS client automatically invokes this callback.
googleApiClientReady = function() {
  gapi.auth.init(function() {
    gapi.client.setApiKey('AIzaSyCBRMNUbFXHHBnQnY0V-hk_PO0xdYAwBio');
    gapi.client.load('youtube', 'v3', function() {
        handleAPILoaded();
    });
  });
}