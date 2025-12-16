// JailTile.js
import { Tile } from "../Tile.js";

export class JailTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "jail";
    this.isJail = true;
    this.sections = ["Just Visiting", "In Jail"];
  }
}
