// GameFacade.js

const GameFacade = (() => {
    // Returns Bank Money
    function getBankStatus() {
    const bankEl = document.querySelector(".bank-money");
    if (!bankEl) throw new Error("Missing .bank-money element in DOM");

    // "Bank Money: $5000" -> 5000
    const totalFunds = parseInt(bankEl.textContent.replace(/[^\d]/g, ""), 10) || 0;

    return { totalFunds };
    }


    function getPlayersStatus() {
    const panels = document.querySelectorAll(".player-panel");
    const players = [];

    panels.forEach(panel => {
        const playerId = parseInt(panel.dataset.playerId, 10);

        const money = parseInt(
        panel.querySelector(".money-value")?.textContent ?? "0",
        10
        );

        const propsText = panel.querySelector("p:nth-of-type(2)")?.textContent ?? "";
        // "Properties: 2 ($300)" -> count=2, worth=300
        const number_of_properties = parseInt((propsText.match(/Properties:\s*(\d+)/)?.[1]) ?? "0", 10);
        const propertyWorthCash = parseInt((propsText.match(/\(\$(\d+)\)/)?.[1]) ?? "0", 10);

        const jailBtn = panel.querySelector(".get-out-jail-btn");
        const is_in_jail = jailBtn ? !jailBtn.disabled : false;

        const goojText = panel.querySelector("p:nth-of-type(3)")?.textContent ?? "";
        const has_get_out_card = goojText.includes("Yes");

        const debtFromText = panel.querySelector("p:nth-of-type(5)")?.textContent ?? "";
        const debt_from_players = parseInt((debtFromText.match(/\$(\d+)/)?.[1]) ?? "0", 10);

        players.push({
        player_id: playerId,
        money,
        number_of_properties,
        propertyWorthCash,
        is_in_jail,
        has_get_out_card,
        debt_from_players
        // NOTE: your UI currently does NOT show debt_to_players, so we can't read it here.
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