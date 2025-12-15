import { PropertyStrategy } from "./tiles/PropertyStrategy.js";
import { TaxStrategy } from "./tiles/TaxStrategy.js";
import { ChanceStrategy } from "./tiles/ChanceStrategy.js";
import { CommunityChestStrategy } from "./tiles/CommunityChestStrategy.js";
import { GoStrategy } from "./tiles/GoStrategy.js";
import { JailStrategy } from "./tiles/JailStrategy.js";
import { GoToJailStrategy } from "./tiles/GoToJailStrategy.js";
import { FreeStrategy } from "./tiles/FreeStrategy.js";

export class TileActionResolver {
  constructor() {
    this.map = new Map([
      ["property", new PropertyStrategy()],
      ["station", new PropertyStrategy()],   // you can split later if you want
      ["utility", new PropertyStrategy()],   // same
      ["tax", new TaxStrategy()],
      ["chance", new ChanceStrategy()],
      ["community", new CommunityChestStrategy()],
      ["go", new GoStrategy()],
      ["jail", new JailStrategy()],
      ["goToJail", new GoToJailStrategy()],
      ["free", new FreeStrategy()],
    ]);
  }

  resolve(tileCategory) {
    return this.map.get(tileCategory) ?? new FreeStrategy(); // default no-op
  }
}
