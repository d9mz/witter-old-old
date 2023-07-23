$(function() {
    // base.js
    // this is not good js

    // Handles ALL comment likes site-wide
    // Could probably do this better. But it works
    $('.comment_like').on('click', function() {
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

    $('.comment_like_reply').on('click', function() {
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

    $('.reweet, .followers-following-list .user-card.wide').on('click', function() {
        window.location.replace(
            $(this).data('target-url')
        );
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

    $(".dropdown-show").on("click", function() {
        // this STINKS!

        console.log($(this).parent())
        $(this).parent().find(".dropdown-content").toggleClass("block");
    });
});