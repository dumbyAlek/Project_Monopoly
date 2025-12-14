// GameFacade.js

const GameFacade = (() => {
    // Returns Bank Money
    function getBankStatus() {
        const bankMoneyEl = document.querySelector(".bank-total-funds"); // add this class in HTML
        return { totalFunds: parseInt(bankMoneyEl.textContent.replace(/\$/,'')) };
    }

    function getPlayersStatus() {
        const panels = document.querySelectorAll(".player-panel");
        const players = [];
        panels.forEach(p => {
            players.push({
                playerId: p.dataset.playerId,
                money: parseInt(p.querySelector(".player-money").textContent.replace(/\$/,'')),
                propertiesCount: parseInt(p.querySelector(".player-properties-count").textContent),
                propertiesWorth: parseInt(p.querySelector(".player-properties-worth").textContent.replace(/\$/,'')),
                inJail: p.querySelector(".get-out-jail-btn").disabled, // fixed logic
                hasGetOutCard: p.querySelector(".player-get-out-card").textContent.includes("Yes"),
                debtToPlayers: parseInt(p.querySelector(".player-debt-to").textContent.replace(/\$/,'')),
                debtFromPlayers: parseInt(p.querySelector(".player-debt-from").textContent.replace(/\$/,'')),
            });
        });
        return players;
    }

    async function saveGame(gameId) {
        const bankStatus = getBankStatus();
        const playersStatus = getPlayersStatus();

        try {
            const response = await fetch('../../Backend/saveGame.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    gameId: gameId,
                    bank: bankStatus,
                    players: playersStatus
                })
            });

            const result = await response.json();
            if(result.success) {
                alert("Game saved successfully!");
            } else {
                alert("Failed to save game.");
            }
        } catch (err) {
            console.error("Save Game Error:", err);
            alert("An error occurred while saving the game.");
        }
    }

    return { getBankStatus, getPlayersStatus, saveGame };
})();

// function resetGameStatus() {
//     document.querySelectorAll(".player-panel").forEach(p => {
//         p.querySelector(".player-money").textContent = "$0";
//         p.querySelector(".player-properties-count").textContent = "0";
//         p.querySelector(".player-properties-worth").textContent = "$0";
//         p.querySelector(".get-out-jail-btn").disabled = false;
//         p.querySelector(".player-get-out-card").textContent = "No";
//         p.querySelector(".player-debt-to").textContent = "$0";
//         p.querySelector(".player-debt-from").textContent = "$0";
//     });
//     document.querySelector(".bank-total-funds").textContent = "$0";
// }