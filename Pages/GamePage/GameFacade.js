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

  // ✅ helper: get a player's live board position from mBoard.js
  function getPlayerPosition(playerId) {
    const arr = window.__mPlayers || [];
    const p = arr.find(x => Number(x.id) === Number(playerId));
    return Number(p?.pos ?? 0);
  }

  // ✅ helper: snapshot properties from window.tiles (requires tiles[i].id to be DB property_id)
  function getPropertiesStatus() {
    const tiles = window.tiles || [];
    return tiles
      .map((t, tileIndex) => ({
        tileIndex,
        property_id: Number(t.id ?? 0),                // DB Property.property_id
        owner_id: (t.owner_id === null || t.owner_id === undefined) ? null : Number(t.owner_id),
        house_count: Number(t.house_count ?? 0),
        hotel_count: Number(t.hotel_count ?? 0),
        is_mortgaged: !!t.is_mortgaged
      }))
      .filter(p => p.property_id > 0);
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

  // ✅ RELIABLE unload save (fetch can get cancelled; beacon usually won't)
  function saveGameBeacon(gameId) {
    try {
      const payload = buildSavePayload(gameId);
      const blob = new Blob([JSON.stringify(payload)], { type: "application/json" });
      navigator.sendBeacon('../../Backend/saveGame.php', blob);
    } catch (e) {
      console.error("Autosave beacon failed:", e);
    }
  }

  // ✅ call once when page loads
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
