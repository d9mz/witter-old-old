$(function() {
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
                console.log("[comment action]", result);
            },
            error: function(request, status, error){
                // Handle errors
                console.error('[comment action] failed to like/dislike! ' + error);
            }
        });        
    });
});