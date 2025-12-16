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

  function getPlayerPosition(playerId) {
    const arr = window.__mPlayers || [];
    const p = arr.find(x => Number(x.id) === Number(playerId));
    return Number(p?.pos ?? 0);
  }

  function getPropertiesStatus() {
    const gp = window.gameProperties || {};
    return Object.keys(gp).map(k => ({
      tileIndex: Number(k),
      property_id: Number(gp[k].id),
      owner_id: gp[k].owner_id ?? null,
      house_count: Number(gp[k].house_count ?? 0),
      hotel_count: Number(gp[k].hotel_count ?? 0),
      is_mortgaged: !!gp[k].is_mortgaged
    })).filter(p => p.property_id > 0);
  }

  function getPlayersStatus() {
    const panels = document.querySelectorAll(".player-panel");
    const players = [];

    panels.forEach(panel => {
      const playerId = parseInt(panel.dataset.playerId, 10);

      const money = parseInt(panel.querySelector(".money-value")?.textContent ?? "0", 10);

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
        position: getPlayerPosition(playerId),
        number_of_properties,
        propertyWorthCash,
        debt_to_players: 0,
        debt_from_players,
        is_in_jail,
        has_get_out_card
      });
    });

    return players;
  }

  function buildSavePayload(gameId) {
    return {
      gameId,
      bank: getBankStatus(),
      players: getPlayersStatus(),
      properties: getPropertiesStatus()
    };
  }

  async function saveGame(gameId) {
    try {
      const payload = buildSavePayload(gameId);

      const response = await fetch('../../Backend/saveGame.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await response.json();
      if (result.success) {
        alert("Game saved successfully!");
      } else {
        alert("Failed to save game: " + (result.message || "Unknown error"));
      }
    } catch (err) {
      console.error("Save Game Error:", err);
      alert("An error occurred while saving the game.");
    }
  }

  function saveGameBeacon(gameId) {
    try {
      const payload = buildSavePayload(gameId);
      const blob = new Blob([JSON.stringify(payload)], { type: "application/json" });
      navigator.sendBeacon('../../Backend/saveGame.php', blob);
    } catch (e) {
      console.error("Autosave beacon failed:", e);
    }
  }

  function enableAutosave(gameId) {
    // pagehide fires on reload + tab close + navigation (better than beforeunload)
    window.addEventListener("pagehide", () => saveGameBeacon(gameId));
  }

  return {
    getBankStatus,
    getPlayersStatus,
    saveGame,
    enableAutosave
  };
})();
