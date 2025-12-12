// Placeholder for future logic
console.log("GamePage loaded with mBoard included");

// Example: update player money
function updatePlayerMoney(playerIndex, amount) {
    const players = document.querySelectorAll(".player-info p");
    if (players[playerIndex]) {
        players[playerIndex].textContent = `Player ${playerIndex+1}: $${amount}`;
    }
}

function attachPlayerEvents() {
    const panels = document.querySelectorAll(".player-panel");
    panels.forEach(p => {
        p.querySelector(".get-out-jail-btn").onclick = () => GameActionsProxy.getOutOfJail(p);
        p.querySelector(".pay-loan-btn").onclick = () => GameActionsProxy.payLoan(p);
        p.querySelector(".pay-debt-btn").onclick = () => GameActionsProxy.payDebt(p);
        p.querySelector(".ask-debt-btn").onclick = () => GameActionsProxy.askDebt(p);
    });
}

// Save Game button
document.getElementById("save-game-btn").onclick = () => GameFacade.saveGame();

// Initial attach
attachPlayerEvents();

// Optional: refresh every 2 seconds to simulate live update
setInterval(() => {
    // Could re-fetch PHP data via AJAX or reload page section
    attachPlayerEvents(); // Reattach events if DOM changes
}, 2000);
