// ================= Visual & DOM Setup =================
export const board = document.querySelector(".board");

// Dice faces
export const diceFaces = [
  "../../Assets/dice1.png",
  "../../Assets/dice2.png",
  "../../Assets/dice3.png",
  "../../Assets/dice4.png",
  "../../Assets/dice5.png",
  "../../Assets/dice6.png"
];

// Tiles setup
export const tiles = [
  { name: "GO", color: "#ffffff" }, { name: "Mediterranean Ave", color: "#8B4513" },
  { name: "Community Chest", color: "#ffffff" }, { name: "Baltic Ave", color: "#8B4513" },
  { name: "Income Tax", color: "#ffffff" }, { name: "Reading Railroad", color: "#000000" },
  { name: "Oriental Ave", color: "#ADD8E6" }, { name: "Chance", color: "#ffffff" },
  { name: "Vermont Ave", color: "#ADD8E6" }, { name: "Connecticut Ave", color: "#ADD8E6" },
  { name: "Jail / Just Visiting", color: "#ffffff" }, { name: "St. Charles Place", color: "#FF00FF" },
  { name: "Electric Company", color: "#ffffff" }, { name: "States Ave", color: "#FF00FF" },
  { name: "Virginia Ave", color: "#FF00FF" }, { name: "Penn. Railroad", color: "#000000" },
  { name: "St. James Place", color: "#FFA500" }, { name: "Community Chest", color: "#ffffff" },
  { name: "Tennessee Ave", color: "#FFA500" }, { name: "New York Ave", color: "#FFA500" },
  { name: "Free Parking", color: "#ffffff" }, { name: "Kentucky Ave", color: "#FF0000" },
  { name: "Chance", color: "#ffffff" }, { name: "Indiana Ave", color: "#FF0000" },
  { name: "Illinois Ave", color: "#FF0000" }, { name: "B. & O. Railroad", color: "#000000" },
  { name: "Atlantic Ave", color: "#FFFF00" }, { name: "Ventnor Ave", color: "#FFFF00" },
  { name: "Water Works", color: "#ffffff" }, { name: "Marvin Gardens", color: "#FFFF00" },
  { name: "Go To Jail", color: "#ffffff" }, { name: "Pacific Ave", color: "#008000" },
  { name: "North Carolina Ave", color: "#008000" }, { name: "Community Chest", color: "#ffffff" },
  { name: "Pennsylvania Ave", color: "#008000" }, { name: "Short Line", color: "#000000" },
  { name: "Chance", color: "#ffffff" }, { name: "Park Place", color: "#0000FF" },
  { name: "Luxury Tax", color: "#ffffff" }, { name: "Boardwalk", color: "#0000FF" }
];

export const mappingLabels = [
  "tile-C1","tile-L1","tile-L2","tile-L3","tile-L4","tile-L5","tile-L6","tile-L7","tile-L8","tile-L9","tile-C2",
  "tile-L10","tile-L11","tile-L12","tile-L13","tile-L14","tile-L15","tile-L16","tile-L17","tile-L18","tile-C3",
  "tile-L19","tile-L20","tile-L21","tile-L22","tile-L23","tile-L24","tile-L25","tile-L26","tile-L27","tile-C4",
  "tile-L28","tile-L29","tile-L30","tile-L31","tile-L32","tile-L33","tile-L34","tile-L35","tile-L36"
];

// Generate tiles on board
export function generateTiles() {
  tiles.forEach((t, i) => {
    const tile = document.createElement("div");
    tile.classList.add("tile");
    tile.id = mappingLabels[i];
    tile.innerHTML = `<div class="tile-color" style="background:${t.color}"></div>
                      <div class="tile-name">${t.name}</div>
                      <div class="player-placeholder"></div>`;
    board.appendChild(tile);
  });
}

// Animate dice
export function getRandomPosition(diceEl, container) {
  const containerRect = container.getBoundingClientRect();
  const diceRect = diceEl.getBoundingClientRect();
  return {
    left: Math.random() * (containerRect.width - diceRect.width),
    top: Math.random() * (containerRect.height - diceRect.height)
  };
}

export function animateDice(diceEl, face) {
  const container = diceEl.parentElement;
  diceEl.style.top = "0px";
  diceEl.style.left = "50%";
  diceEl.style.transform = "translateX(-50%) rotate(0deg) scale(1)";
  diceEl.innerHTML = "";
  const pos = getRandomPosition(diceEl, container);
  const rotations = Math.floor(Math.random() * 720) + 360;
  const scale = 1 + Math.random() * 0.5;
  setTimeout(() => {
    diceEl.style.top = `${pos.top}px`;
    diceEl.style.left = `${pos.left}px`;
    diceEl.style.transform = `rotate(${rotations}deg) scale(${scale})`;
  }, 50);
  setTimeout(() => {
    diceEl.innerHTML = `<img src="${face}" width="50" height="50" />`;
    diceEl.style.transform = "translateX(-50%) rotate(0deg) scale(1)";
  }, 900);
}