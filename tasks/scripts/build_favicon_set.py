#!/usr/bin/env python3
"""
Generate the canonical favicon set from a single source image.

Usage:
  python3 tasks/scripts/build_favicon_set.py \
    --source assets/favicon/source.png \
    --out-dir .
"""

from __future__ import annotations

import argparse
from pathlib import Path

from PIL import Image


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Build favicon assets from one source image.")
    parser.add_argument("--source", required=True, help="Path to source PNG (recommended 512x512+).")
    parser.add_argument(
        "--out-dir",
        default=".",
        help="Directory where favicon files are written (default: current directory).",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    source = Path(args.source).resolve()
    out_dir = Path(args.out_dir).resolve()
    out_dir.mkdir(parents=True, exist_ok=True)

    base = Image.open(source).convert("RGBA")

    icon_512 = base.resize((512, 512), Image.Resampling.LANCZOS)
    icon_192 = base.resize((192, 192), Image.Resampling.LANCZOS)
    icon_180 = base.resize((180, 180), Image.Resampling.LANCZOS)
    icon_32 = base.resize((32, 32), Image.Resampling.LANCZOS)
    icon_16 = base.resize((16, 16), Image.Resampling.LANCZOS)

    # Canonical files used in this repository.
    icon_512.save(out_dir / "site-icon.png", format="PNG")
    icon_512.save(out_dir / "favicon.png", format="PNG")
    icon_192.save(out_dir / "site-icon-192.png", format="PNG")
    icon_180.save(out_dir / "apple-touch-icon.png", format="PNG")
    icon_32.save(out_dir / "favicon-32x32.png", format="PNG")
    icon_16.save(out_dir / "favicon-16x16.png", format="PNG")

    icon_512.save(
        out_dir / "favicon.ico",
        format="ICO",
        sizes=[(16, 16), (24, 24), (32, 32), (48, 48), (64, 64)],
    )

    generated = [
        out_dir / "favicon.ico",
        out_dir / "favicon.png",
        out_dir / "site-icon.png",
        out_dir / "site-icon-192.png",
        out_dir / "apple-touch-icon.png",
        out_dir / "favicon-32x32.png",
        out_dir / "favicon-16x16.png",
    ]
    print("Generated files:")
    for path in generated:
        print(f"- {path}")


if __name__ == "__main__":
    main()
