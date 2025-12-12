// GameActionsProxy.js

const GameActionsProxy = (() => {
    function getOutOfJail(playerPanel) {
        const btn = playerPanel.querySelector(".get-out-jail-btn");
        if (btn.disabled) {
            alert("Player is not in jail!");
            return;
        }
        btn.disabled = true;
        alert("Player got out of jail!"); // Replace with actual PHP update
    }

    function payLoan(playerPanel) {
        const amount = parseInt(prompt("Enter loan amount to pay:"));
        if (isNaN(amount) || amount <= 0) {
            alert("Invalid amount!");
            return;
        }
        alert(`Paid $${amount} toward loan!`); // Replace with PHP DB logic
    }

    function payDebt(playerPanel) {
        const target = prompt("Pay debt to which player ID?");
        const amount = parseInt(prompt("Enter amount:"));
        if (isNaN(amount) || amount <= 0) { alert("Invalid!"); return; }
        alert(`Paid $${amount} to player ${target}`); // Replace with PHP DB logic
    }

    function askDebt(playerPanel) {
        const target = prompt("Ask debt from which player ID?");
        const amount = parseInt(prompt("Enter amount:"));
        if (isNaN(amount) || amount <= 0) { alert("Invalid!"); return; }
        alert(`Requested $${amount} from player ${target}`); // Replace with PHP DB logic
    }

    return { getOutOfJail, payLoan, payDebt, askDebt };
})();
