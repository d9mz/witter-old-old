$(function() {
    // Handles ALL comment likes site-wide
    // Could probably do this better. But it works
    $('.comment_like').on('click', function() {
        let commentID = $(this).data('comment-id'); // Get the data attribute value
        let commentLabel = $(this).find('.comment-action-text');

        console.info("[comment action] like #" + commentID);

        $(this).toggleClass("active");
        if($(this).hasClass("active")) {
            let commentLikes = parseInt(commentLabel.text()) + 1;
            commentLabel.text(commentLikes);
        } else {
            let commentLikes = parseInt(commentLabel.text()) - 1;
            commentLabel.text(commentLikes);
        }
    });
});