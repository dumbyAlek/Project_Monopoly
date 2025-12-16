// StationTile.js
import { Tile } from "../Tile.js";

export class StationTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "station";
  }
}
