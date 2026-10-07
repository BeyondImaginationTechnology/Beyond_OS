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


def setup_audio(scene, audio_path):
    if scene.sequence_editor:
        scene.sequence_editor_clear()
    editor = scene.sequence_editor_create()
    sound = editor.strips.new_sound('Chris_Ryan_Narration', audio_path, channel=1, frame_start=1)
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


def main():
    args = arguments()
    if not os.path.isfile(args.audio):
        raise RuntimeError('Narration MP3 was not found: ' + args.audio)

    scene = bpy.context.scene
    scene.render.engine = 'BLENDER_EEVEE_NEXT'
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

    camera = bpy.data.objects.get('Chris_Presenter_Camera')
    if camera:
        scene.camera = camera

    set_text('DB_Study_Verse_Title', 'DAILY BIBLE VERSE')
    set_text('DB_Study_Verse_Reference', args.reference.upper())
    scene['daily_breath_reference'] = args.reference
    scene['daily_breath_passage'] = args.passage
    scene['narrator'] = 'Chris · Ryan (ElevenLabs)'
    scene['facial_sync_note'] = 'Body performance is timed to narration; this rig has no mouth shape keys or facial bones.'

    end_frame = setup_audio(scene, args.audio)
    # Give the final spoken word a short visual hold.
    scene.frame_start = 1
    scene.frame_end = max(48, end_frame + int(scene.render.fps * 1.5))
    loop_body_performance(scene, scene.frame_end)
    scene.frame_set(1)
    bpy.ops.render.render(animation=True)


if __name__ == '__main__':
    main()
