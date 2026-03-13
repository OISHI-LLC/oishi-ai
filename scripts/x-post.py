#!/usr/bin/env python3
"""X (Twitter) auto-poster: reads from content queue and posts scheduled tweets."""

import json
import os
import sys
from datetime import datetime, timezone, timedelta

import tweepy

JST = timezone(timedelta(hours=9))
QUEUE_PATH = os.path.join(os.path.dirname(__file__), "..", "tasks", "x-content-queue.json")
REPO_ROOT = os.path.join(os.path.dirname(__file__), "..")

def get_client():
    return tweepy.Client(
        consumer_key=os.environ["X_API_KEY"],
        consumer_secret=os.environ["X_API_KEY_SECRET"],
        access_token=os.environ["X_ACCESS_TOKEN"],
        access_token_secret=os.environ["X_ACCESS_TOKEN_SECRET"],
    )

def get_api():
    """v1.1 API for media upload."""
    auth = tweepy.OAuth1UserHandler(
        os.environ["X_API_KEY"],
        os.environ["X_API_KEY_SECRET"],
        os.environ["X_ACCESS_TOKEN"],
        os.environ["X_ACCESS_TOKEN_SECRET"],
    )
    return tweepy.API(auth)

def load_queue():
    with open(QUEUE_PATH, "r", encoding="utf-8") as f:
        return json.load(f)

def save_queue(queue):
    with open(QUEUE_PATH, "w", encoding="utf-8") as f:
        json.dump(queue, f, ensure_ascii=False, indent=2)
        f.write("\n")

def post_due_tweets(dry_run=False):
    queue = load_queue()
    now = datetime.now(JST)
    posted = 0
    errors = 0

    for item in queue:
        if item["status"] != "pending":
            continue
        scheduled = datetime.fromisoformat(item["scheduled"])
        if scheduled > now:
            continue

        if dry_run:
            print(f"[DRY RUN] Would post id={item['id']}: {item['text'][:50]}...")
            item["status"] = "dry_run"
            posted += 1
            continue

        try:
            client = get_client()
            media_ids = None

            # Upload image if specified
            if item.get("image"):
                image_path = os.path.join(REPO_ROOT, item["image"])
                if os.path.exists(image_path):
                    api = get_api()
                    media = api.media_upload(image_path)
                    media_ids = [media.media_id]
                    print(f"  Uploaded media: {item['image']} -> {media.media_id}")
                else:
                    print(f"  Warning: image not found: {image_path}", file=sys.stderr)

            response = client.create_tweet(text=item["text"], media_ids=media_ids)
            tweet_id = response.data["id"]
            item["status"] = "posted"
            item["tweet_id"] = tweet_id
            item["posted_at"] = now.isoformat()
            print(f"Posted id={item['id']} -> tweet {tweet_id}")
            posted += 1
        except tweepy.errors.Forbidden as e:
            item["status"] = "error"
            item["error"] = str(e)
            detail = getattr(e, 'response', None)
            body = detail.text if detail else "no response body"
            print(f"Error posting id={item['id']}: {e}", file=sys.stderr)
            print(f"  Response body: {body}", file=sys.stderr)
            print(f"  Tweet length: {len(item['text'])} chars", file=sys.stderr)
            errors += 1
        except Exception as e:
            item["status"] = "error"
            item["error"] = str(e)
            print(f"Error posting id={item['id']}: {e}", file=sys.stderr)
            errors += 1

    save_queue(queue)
    print(f"Done. {posted} posted, {errors} error(s).")
    if errors > 0:
        sys.exit(1)

if __name__ == "__main__":
    dry_run = "--dry-run" in sys.argv
    post_due_tweets(dry_run=dry_run)
