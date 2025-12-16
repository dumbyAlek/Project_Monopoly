// FreeTile.js
import { Tile } from "../Tile.js";

export class FreeTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "free";
  }
}
