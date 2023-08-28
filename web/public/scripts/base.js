$(function() {
    // base.js
    // this is not good js

    $('.unlink-account').on('click', function() {
        $.ajax({
            url: '/settings/unlink/',
            type: 'POST',
            data: JSON.stringify({}),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result){
                // Do something with the result
            },
            error: function(request, status, error){
                // Handle errors
            }
        });        

        createAlert(4, `Successfully unlinked your last.fm account!`);
    });

    $('.follow_button').on('click', function() {
        event.stopPropagation();
        event.preventDefault();
        
        let followTarget = $(this).data('follow-target');
        let following = $(this).data('following'); 

        console.info("[follow action] follow uid " + followTarget);
        console.info("[follow action] following? " + following);

        if($(this).text().trim() == "follow") {
            $(this).text("unfollow");
        } else {
            $(this).text("follow");
        }

        console.info("[follow action] attempting follow... ")
        
        // Regardless, send POST request

        $.ajax({
            url: '/actions/user/' + followTarget + '/follow',
            type: 'POST',
            data: JSON.stringify({weet_uid: followTarget}),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result){
                // Do something with the result
                console.log("[follow action] ", result);
            },
            error: function(request, status, error){
                // Handle errors
                console.error('[follow action] failed to follow/unfollow! ' + error);
            }
        });        
    });

    let characters = 200;
    let characterCounter = $('span#js_char_remaining');
    let submitButton = $('#js_submit')

    $("#js_comment").on('input', function() {
        let value = $('textarea#js_comment').val()
        let charactersLeft = characters - value.length;

        characterCounter.text(charactersLeft);

        if(charactersLeft < 0) {
            characterCounter.css("color", "darkred");
            submitButton.prop("disabled", true);
        } else {
            characterCounter.css("color", "unset");
            submitButton.prop("disabled", false);
        }
    });


});

// for dynamic bullshit
$(document).on('click', '.dropdown-show', function() {
    console.log($(this).parent())
    $(this).parent().find(".dropdown-content").toggleClass("block");
});

$(document).on('click', '.delete-weet-action', function() {
    console.log("fart");
    let weetTarget = $(this).data('target');

    $.ajax({
        url: '/actions/post/' + weetTarget + '/delete',
        type: 'POST',
        data: JSON.stringify({weet_id: weetTarget}),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(result){
            // Do something with the result
            createAlert(4, `Successfully deleted your weet!`);
            $(`.weet[data-weet-id="${weetTarget}"]`).fadeOut();
            console.log("[comment action] ", result);
        },
        error: function(request, status, error){
            // 403
            createAlert(2, `Your weet could not be deleted due to an unknown error!!`);
            console.error('[comment action] failed to delete! ' + error);
        }
    });        
});

$(document).on('click', '.block-user-action', function() {
    console.log("fart");
    let userTarget = $(this).data('target');

    $.ajax({
        url: '/actions/user/' + userTarget + '/block',
        type: 'POST',
        data: JSON.stringify({weet_id: userTarget}),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(result){
            // Do something with the result
            if(result.action == "block") {
                createAlert(4, `Successfully blocked this user!`);
            } else if(result.action == "unblocked") {
                createAlert(4, `Successfully unblocked this user!`);
            }
            console.log("[block action] ", result);
        },
        error: function(request, status, error){
            // 403
            createAlert(2, `This user could not be blocked due to an unknown error!!`);
            console.error('[block action] failed to block! ' + error);
        }
    });        
});


// Handles ALL comment likes site-wide
// Could probably do this better. But it works
$(document).on('click', '.comment_like', function() {
    let commentID = $(this).data('comment-id'); // Get the data attribute value
    let commentLabel = $(this).find('.comment-action-text');

    console.info("[comment action] like #" + commentID);

    $(this).toggleClass("active");
    if($(this).hasClass("active")) {
        // LIKE!!!

        let commentLikes = parseInt(commentLabel.text()) + 1;
        commentLabel.text(commentLikes);
    } else {
        // REMOVE LIKE!!!

        let commentLikes = parseInt(commentLabel.text()) - 1;
        commentLabel.text(commentLikes);
    }

    // Regardless, send POST request
    $.ajax({
        url: '/actions/post/' + commentID + '/like',
        type: 'POST',
        data: JSON.stringify({weet_id: commentID}),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(result){
            // Do something with the result
            console.log("[comment action] ", result);
        },
        error: function(request, status, error){
            // Handle errors
            console.error('[comment action] failed to like/dislike! ' + error);
        }
    });        
});

$(document).on('click', '.comment_like_reply', function() {
    let commentID = $(this).data('comment-id'); // Get the data attribute value
    let commentLabel = $(this).find('.comment-action-text');

    console.info("[reply action] like #" + commentID);

    $(this).toggleClass("active");
    if($(this).hasClass("active")) {
        // LIKE!!!

        let commentLikes = parseInt(commentLabel.text()) + 1;
        commentLabel.text(commentLikes);
    } else {
        // REMOVE LIKE!!!

        let commentLikes = parseInt(commentLabel.text()) - 1;
        commentLabel.text(commentLikes);
    }

    // Regardless, send POST request
    $.ajax({
        url: '/actions/reply/' + commentID + '/like',
        type: 'POST',
        data: JSON.stringify({weet_id: commentID}),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(result){
            // Do something with the result
            console.log("[reply action] ", result);
        },
        error: function(request, status, error){
            // Handle errors
            console.error('[reply action] failed to like/dislike! ' + error);
        }
    });        
});

$(document).on('click', '.reweet, .followers-following-list, .user-card.wide', function() {
    window.location.replace(
        $(this).data('target-url')
    );
});