// registerTiles.js
import { TileFactory } from "./TileFactory.js";

import { PropertyTile } from "./tiles/PropertyTile.js";
import { StationTile } from "./tiles/StationTile.js";
import { ChanceTile } from "./tiles/ChanceTile.js";
import { CommunityTile } from "./tiles/CommunityTile.js";
import { JailTile } from "./tiles/JailTile.js";
import { GoToJailTile } from "./tiles/GoToJailTile.js";
import { GoTile } from "./tiles/GoTile.js";
import { TaxTile } from "./tiles/TaxTile.js";
import { UtilityTile } from "./tiles/UtilityTile.js";
import { FreeTile } from "./tiles/FreeTile.js";
import { OtherTile } from "./tiles/OtherTile.js";

TileFactory.register("property", PropertyTile);
TileFactory.register("station", StationTile);
TileFactory.register("chance", ChanceTile);
TileFactory.register("community", CommunityTile);
TileFactory.register("jail", JailTile);
TileFactory.register("goToJail", GoToJailTile);
TileFactory.register("go", GoTile);
TileFactory.register("tax", TaxTile);
TileFactory.register("utility", UtilityTile);
TileFactory.register("free", FreeTile);
TileFactory.register("other", OtherTile);
