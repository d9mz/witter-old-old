let $container = $('.weet-container').infiniteScroll({
    // options
    path: '/v1/api/load_weets/{{#}}',
    append: false,
    history: false,
});

$container.on('load.infiniteScroll', function( event, response ) {
    // get posts from response
    let $posts = $( response ).find('.weet-container');
    // append posts after images loaded
    $posts.imagesLoaded( function() {
      $container.infiniteScroll( 'appendItems', $posts );
    });
});