$(function() {
    // admin.js
    // this is not good js either

    // Handles ALL site moderation stuffs for admins site-wide
    // Could probably do this better. But it works
    $('button.moderate').on('click', function() {
        let moderationType = $(this).data("moderation-type");
        let moderationAction = $(this).data("moderation-action");
        let moderationTarget = $(this).data("moderation-target");
        let constructedURL = '/moderate/';
        let payload; // this is the data that will be POST'd

        if(moderationType == "css") {
            constructedURL += "css/" + moderationAction;
            payload = {
                target: moderationTarget, 
                action: moderationAction,
            }; 

            console.log(moderationAction, moderationType, moderationTarget, constructedURL);
            createAlert(4, `Moderated uid ${moderationTarget} type ${moderationType} action ${moderationAction}`);
        }

        // all moderation actions go under /moderate/
        $.ajax({
            url: constructedURL,
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result){
                // Do something with the result
                console.log(`[${moderationAction} action]`, result);
            },

            error: function(request, status, error){
                // Handle errors
                console.error(`[${moderationAction} action] failed to moderate! ${error}`);
            }
        });
    });
});