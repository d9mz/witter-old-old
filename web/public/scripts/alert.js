/**
 * @param {int} alertType - alertType (Info, Warning, Error, Fatal, Success) (enum, index)
 * @param {string} alertText - The text to display
 */
function createAlert(alertType = 0, alertText) {
    // this looks kinda ugly

    let alertTypeClass;
    switch(alertType) {
        case 0: alertTypeClass = "info";    break;
        case 1: alertTypeClass = "warning"; break;
        case 2: alertTypeClass = "error";   break;
        case 3: alertTypeClass = "fatal";   break;
        case 4: alertTypeClass = "success"; break;
        default: break;
    }

    // thanks ! https://stackoverflow.com/questions/867916/creating-a-div-element-in-jquery

    let alertContainer = $('<div>', {
        class: 'container alert-' + alertTypeClass,
    }).insertAfter('nav');    

    let alertTextContainer = $('<span>', {
        class: 'padding inline-block'
    });

    // append the alertTextContainer to the alertContainer
    alertContainer.append(alertTextContainer);
    alertTextContainer.text(alertText);
}