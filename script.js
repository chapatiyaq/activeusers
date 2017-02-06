document.addEventListener("DOMContentLoaded", function(event) {
    var wikiButtonsDiv = document.querySelector('.wiki-buttons');
    var buttons = {
        'clear': {
            'text': 'Clear selection',
            'onClick': function() {
                document.querySelectorAll('.wiki-button input').forEach(function(b) {
                    b.checked = false;
                });
            }
        },
        'selectAll': {
            'text': 'Select all',
            'onClick': function() {
                document.querySelectorAll('.wiki-button input').forEach(function(b) {
                    b.checked = true;
                });
            }
        },
        'selectAllFull': {
            'text': 'Select full wikis',
            'onClick': function() {
                document.querySelectorAll('.wiki-button input').forEach(function(b) {
                    b.checked = b.matches('.wiki-type-full input');
                });
            }
        },
        'selectAllAlpha': {
            'text': 'Select alpha wikis',
            'onClick': function() {
                document.querySelectorAll('.wiki-button input').forEach(function(b) {
                    b.checked = b.matches('.wiki-type-alpha input');
                });
            }
        }
    };
    var helpersDiv = document.createElement('div');
    helpersDiv.setAttribute('class', 'wiki-buttons-helpers');
    wikiButtonsDiv.after(helpersDiv);
    var button;
    for (var i in buttons)
    {
        button = document.createElement('button');
        button.setAttribute('type','button');
        button.innerHTML = buttons[i].text;
        button.addEventListener('click', buttons[i].onClick);
        helpersDiv.append(button);
    }
});