import argparse
import math
import os
import sys

import bpy


def arguments():
    values = sys.argv[sys.argv.index('--') + 1:] if '--' in sys.argv else []
    parser = argparse.ArgumentParser(description='Render Chris presenting the Daily Breath Bible verse.')
    parser.add_argument('--audio', required=True)
    parser.add_argument('--output', required=True)
    parser.add_argument('--reference', required=True)
    parser.add_argument('--passage', default='')
    return parser.parse_args(values)


def set_text(name, value):
    obj = bpy.data.objects.get(name)
    if obj and obj.type == 'FONT':
        obj.data.body = value


INTRO_FRAMES = 96  # Four seconds at 24 fps.
OUTRO_HOLD_FRAMES = 36


def setup_audio(scene, audio_path):
    if scene.sequence_editor:
        scene.sequence_editor_clear()
    editor = scene.sequence_editor_create()
    # The first four seconds are the visual title/reference opener. Prayan begins
    # when the edit cuts to Chris's close presenter shot.
    sound = editor.strips.new_sound('Chris_Prayan_Narration', audio_path, channel=1, frame_start=INTRO_FRAMES + 1)
    return max(1, int(math.ceil(sound.frame_final_duration)))


def loop_body_performance(scene, end_frame):
    rig = bpy.data.objects.get('Chris_Rig')
    if not rig or not rig.animation_data or not rig.animation_data.action:
        return
    action = rig.animation_data.action
    start = int(action.frame_range[0])
    length = max(1, int(math.ceil(action.frame_range[1] - start + 1)))
    track = rig.animation_data.nla_tracks.new()
    track.name = 'Chris Daily Verse Performance'
    strip = track.strips.new('Chris seated talking', 1, action)
    strip.action_frame_start = start
    strip.action_frame_end = start + length - 1
    strip.frame_start = 1
    strip.frame_end = end_frame
    strip.repeat = max(1.0, end_frame / length)
    rig.animation_data.action = None


def set_episode_cameras(scene, closing_frame):
    wide = bpy.data.objects.get('Chris_Morning_Wide_Camera') or bpy.data.objects.get('Chris_Presenter_Camera')
    close = bpy.data.objects.get('Chris_Morning_Close_Camera') or wide
    if not wide:
        return
    scene.timeline_markers.clear()
    opening = scene.timeline_markers.new('Morning Verse opening', frame=1)
    opening.camera = wide
    reading = scene.timeline_markers.new('Chris reads today\'s verse', frame=INTRO_FRAMES + 1)
    reading.camera = close
    closing = scene.timeline_markers.new('Carry this verse with you', frame=closing_frame)
    closing.camera = wide
    scene.camera = wide


def main():
    args = arguments()
    if not os.path.isfile(args.audio):
        raise RuntimeError('Narration MP3 was not found: ' + args.audio)

    scene = bpy.context.scene
    scene.render.engine = 'BLENDER_EEVEE'
    scene.render.resolution_x = 1920
    scene.render.resolution_y = 1080
    scene.render.resolution_percentage = 100
    scene.render.fps = 24
    scene.render.image_settings.file_format = 'FFMPEG'
    scene.render.ffmpeg.format = 'MPEG4'
    scene.render.ffmpeg.codec = 'H264'
    scene.render.ffmpeg.audio_codec = 'AAC'
    scene.render.ffmpeg.constant_rate_factor = 'MEDIUM'
    scene.render.filepath = args.output

    camera = bpy.data.objects.get('Chris_Morning_Wide_Camera') or bpy.data.objects.get('Chris_Presenter_Camera')
    if camera:
        scene.camera = camera

    set_text('DB_Study_Verse_Title', 'DAILY BIBLE VERSE')
    set_text('DB_Study_Verse_Reference', args.reference.upper())
    scene['daily_breath_reference'] = args.reference
    scene['daily_breath_passage'] = args.passage
    scene['narrator'] = 'Chris · Prayan (ElevenLabs)'
    scene['facial_sync_note'] = 'Body performance is timed to narration; this rig has no mouth shape keys or facial bones.'

    narration_frames = setup_audio(scene, args.audio)
    # Give the final spoken word a short visual hold.
    scene.frame_start = 1
    scene.frame_end = max(INTRO_FRAMES + OUTRO_HOLD_FRAMES, INTRO_FRAMES + narration_frames + OUTRO_HOLD_FRAMES)
    set_episode_cameras(scene, scene.frame_end - OUTRO_HOLD_FRAMES + 1)
    loop_body_performance(scene, scene.frame_end)
    scene['episode_timeline'] = {
        'opening': 'Frames 1-96 · four-second Morning Verse opener',
        'reading': 'Frame 97 · Prayan narration and Chris close presenter shot',
        'outro': '36-frame visual hold after Prayan finishes',
    }
    scene.frame_set(1)
    bpy.ops.render.render(animation=True)


if __name__ == '__main__':
    main()
