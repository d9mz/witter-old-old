// this code sucks balls and im sorry for whoever has to read this
function getRandomInt(min, max) {
    min = Math.ceil(min);
    max = Math.floor(max);
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function between(x, min, max) {
    return x >= min && x <= max;
}

// spritesheet credit: https://www.angelfire.com/comics/ffzar/domo-kunsheet.GIF
/////////////////////////////////////////////////////////////////////

const action = ["idle", "walk"];
const walkTime = 100; // ms

let width = window.innerWidth;
let height = window.innerHeight;

let idleCount = 0;

$(window).on("resize", function() {
    let width = window.innerWidth;
    let height = window.innerHeight;
    console.log(`resized: new = ${width}x${height}`);
});

$(function() {
    const domoKunContainer = $(".domo-kun-area");
    const domoKun = domoKunContainer.find($(".domo-kun"));

    const domoWidth = domoKun.width();

    // first, start domokun off somewhere random on page
    // make visible after setting random place
    domoKun.css("left", `${getRandomInt(width * 0.2, width - domoWidth)}px`);
    domoKun.css("display", "block");

    console.log(`${width}x${height}`);
    console.log("ready");
    
    // start domo logic
    async function task(i) { // 3
        let currentAction = getRandomInt(0, action.length - 1); // current action

        // if idled for too long do anything else
        console.log(`${idleCount} idleCount`)
        if (idleCount > 2) {
            currentAction = getRandomInt(1, action.length - 1);
            idleCount = 0;
        }

        let actualAction = action[currentAction];
        console.log(`currentaction: ${actualAction} ${currentAction}`);

        task(currentAction);
    }

    async function main() {
        for (let i = 0; i < 100; i += 10) {
            for (let j = 0; j < 10; j++) { // 1
                if (j % 2) { // 2
                    await task(i + j);
                }
            }
        }
    }

    async function task(i) {
        let currentAction = getRandomInt(0, action.length - 1); // current action
        let actualAction = action[currentAction];

        console.log(`currentaction: ${actualAction} ${currentAction}`);

        if (actualAction == "idle") {
            $(domoKun).css("transform", "scaleX(1)");
            $(domoKun).css("background-image", "url(/domo_sprites/idle1.png)");
            idleCount++;
            await timer(getRandomInt(1000, 5000));
        } else if (actualAction == "walk") {
            let currentOffset = domoKun.position();
            let randomPosition = getRandomInt(0, width - domoWidth);

            while (between(currentOffset, -(width * 0.2), width * 0.2)) {
                randomPosition = getRandomInt(0, width - domoWidth);
            }
            console.log(`walking to: ${randomPosition}`);
  
            console.log(currentOffset);
            let moveRight;
            if (randomPosition > currentOffset.left) {
                moveRight = true;
            } else {
                moveRight = false;
            }

            function animateDomo(moveRight) {
                return new Promise(resolve => {
                    if (!moveRight) {
                        $(domoKun).css("transform", "scaleX(-1)");
                    }

                    $(domoKun).css({
                        "left": currentOffset.left
                    }).animate({
                        "left": `${randomPosition}px`
                    }, getRandomInt(5, 7) * 1000, "linear", function() {
                        resolve();
                    });
                });
            }

            while (currentOffset.left != randomPosition) {
                $(domoKun).css("background-image", "url(/domo_sprites/walk.gif)");
                await animateDomo(moveRight);
                currentOffset = domoKun.position();
            }
            // task("idle"); // idle, dont constalty walk
        }

        console.log(`Task ${actualAction} done!`);
    }

    main();

    function timer(ms) {
        return new Promise(res => setTimeout(res, ms));
    }
});