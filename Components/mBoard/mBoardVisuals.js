// mBoardVisuals.js
export const board = document.querySelector(".board");
import { TileFactory } from './TileFactory.js';
import { TileDecorator } from './TileDecorator.js';

// Dice faces
export const diceFaces = [
  "../../Assets/dice1.webp",
  "../../Assets/dice2.webp",
  "../../Assets/dice3.webp",
  "../../Assets/dice4.webp",
  "../../Assets/dice5.webp",
  "../../Assets/dice6.webp"
];

// TileFactory.createTile("property", ),

// Tiles setup
export const tiles = [
  TileFactory.createTile("go", { name: "", img: "../../Assets/GO.webp" }),
  TileFactory.createTile("property", { name: "OLD KENT ROAD", color: "#4a270eff" }),
  TileFactory.createTile("community", { name: "COMMUNITY CHEST", img: "../../Assets/chest.webp" }), 
  TileFactory.createTile("property", { name: "WHITECHAPEL ROAD", color: "#4a270eff" }),
  TileFactory.createTile("tax", { name: "INCOME TAX", img: "../../Assets/tax.webp" }), 
  TileFactory.createTile("station", { name: "KINGS ROSS STATION", img: "../../Assets/train.webp" }),
  TileFactory.createTile("property", { name: "THE ANGEL ISLINGTON", color: "#33a3ffff" }),
  TileFactory.createTile("chance", { name: "CHANCE", img: "../../Assets/ques.webp" }),
  TileFactory.createTile("property", { name: "EUSTON ROAD", color: "#33a3ffff" }),
  TileFactory.createTile("property", { name: "PENTONVILLE ROAD", color: "#33a3ffff" }),
  TileFactory.createTile("jail", { name: "Jail / Just Visiting", img: "../../Assets/jail.webp"}),
  TileFactory.createTile("property", { name: "PALL MALL", color: "#f200ffff" }),
  TileFactory.createTile("utility", { name: "ELECTRIC COMPANY", img: "../../Assets/bulb.webp" }),
  TileFactory.createTile("property", { name: "WHITE HALL", color: "#f200ffff" }),
  TileFactory.createTile("property", { name: "NORTHUMBERL'D AVENUE", color: "#f200ffff" }),
  TileFactory.createTile("station", { name: "MARYLEBONE STATION", img: "../../Assets/train.webp" }),
  TileFactory.createTile("property", { name: "BOW STREET", color: "#ff7b00ff" }),
  TileFactory.createTile("community", { name: "COMMUNITY CHEST", img: "../../Assets/chest.webp" }),
  TileFactory.createTile("property", { name: "MARLBOROUGH STREET", color: "#ff7b00ff" }),
  TileFactory.createTile("property", { name: "VINE STREET", color: "#ff7b00ff" }),
  TileFactory.createTile("free", { name: "FREE PARKING", img: "../../Assets/free.webp" }),
  TileFactory.createTile("property", { name: "STRAND", color: "#b50000ff" }),
  TileFactory.createTile("chance", { name: "CHANCE", img: "../../Assets/ques.webp" }),
  TileFactory.createTile("property", { name: "FLEET STREET", color: "#b50000ff" }),
  TileFactory.createTile("property", { name: "TRAFALGAR SQUARE", color: "#b50000ff" }),
  TileFactory.createTile("station", { name: "FENCHURCH ST. STATION", img: "../../Assets/train.webp" }),
  TileFactory.createTile("property", { name: "LEICSTER SQUARE", color: "#fff200ff" }),
  TileFactory.createTile("property", { name: "COVENTRY STREET", color: "#fff200ff" }),
  TileFactory.createTile("utility", { name: "WATER WORKS", img: "../../Assets/tap.webp" }),
  TileFactory.createTile("property", { name: "PACCADILLY", color: "#fff200ff" }),
  TileFactory.createTile("goToJail", { name: "GO TO JAIL", img: "../../Assets/go_jail.webp" }),
  TileFactory.createTile("property", { name: "REGENT STREET", color: "#096a00ff" }),
  TileFactory.createTile("property", { name: "OXFORD STREET", color: "#096a00ff" }),
  TileFactory.createTile("community", { name: "COMMUNITY CHEST", img: "../../Assets/chest.webp" }),
  TileFactory.createTile("property", { name: "BOND STREET", color: "#096a00ff" }),
  TileFactory.createTile("station", { name: "LIVERPOOL ST. STATION", img: "../../Assets/train.webp" }),
  TileFactory.createTile("chance", { name: "CHANCE", img: "../../Assets/ques.webp" }),
  TileFactory.createTile("property", { name: "PARK LANE", color: "#6802c1ff" }),
  TileFactory.createTile("tax", { name: "LUXURY TAX", img: "../../Assets/tax.webp" }), 
  TileFactory.createTile("property", { name: "MAYFAIR", color: "#6802c1ff" }),
];

export const mappingLabels = [
  "tile-C1","tile-L1","tile-L2","tile-L3","tile-L4","tile-L5","tile-L6","tile-L7","tile-L8","tile-L9","tile-C2",
  "tile-L10","tile-L11","tile-L12","tile-L13","tile-L14","tile-L15","tile-L16","tile-L17","tile-L18","tile-C3",
  "tile-L19","tile-L20","tile-L21","tile-L22","tile-L23","tile-L24","tile-L25","tile-L26","tile-L27","tile-C4",
  "tile-L28","tile-L29","tile-L30","tile-L31","tile-L32","tile-L33","tile-L34","tile-L35","tile-L36"
];

export function generateTiles() {
  tiles.forEach((t, i) => {
    const tile = document.createElement("div");
    tile.classList.add("tile");
    tile.id = mappingLabels[i];

    // Picture or color
    tile.innerHTML = t.img 
      ? `<img class="tile-pic" src="${t.img}" alt="${t.name}" />` 
      : `<div class="tile-color" style="background:${t.color}"></div>`;

    // Name
    tile.innerHTML += `<div class="tile-name">${t.name}</div>
                      <div class="houses-container"></div>
                      <div class="player-placeholder"></div>
                      <div class="tile-details">
                        <p>Price: ${t.price ?? "N/A"}</p>
                        <p>Rent: ${t.rent ?? "N/A"}</p>
                        <button class="buy-btn">Buy</button>
                        <button class="sell-btn">Sell</button>
                        <button class="placeHouseOrHotel-btn">Place House/Hotel</button>
                      </div>`;

    // If tile has sections (like Jail)
    if (t.sections) {
      TileDecorator.addSections(tile, t.sections);
    }

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