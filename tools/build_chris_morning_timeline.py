import bpy

OUTPUT = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\DB_Christian_StudyV14_Morning_Verse_Timeline.blend"


def marker(scene, name, frame, camera):
    item = scene.timeline_markers.new(name, frame=frame)
    item.camera = camera


def main():
    scene = bpy.context.scene
    wide = bpy.data.objects['Chris_Morning_Wide_Camera']
    close = bpy.data.objects['Chris_Morning_Close_Camera']

    # 40-second editable template at 24 fps. The nightly renderer keeps the
    # opening and then moves the closing hold to Ryan's actual audio endpoint.
    scene.frame_start = 1
    scene.frame_end = 960
    scene.render.fps = 24
    scene.timeline_markers.clear()
    marker(scene, '01 · MORNING VERSE OPENING · 0:00–0:04', 1, wide)
    marker(scene, '02 · CHRIS READS TODAY\'S VERSE · 0:04–0:35', 97, close)
    marker(scene, '03 · CLOSING HOLD · 0:35–0:40', 841, wide)
    scene.camera = wide

    scene['timeline_guide'] = 'Frame 1 opening; frame 97 Ryan starts; frame 841 closing hold. Import Ryan WAV at frame 97.'
    scene['render_instruction'] = 'Use Ctrl+F12 after choosing FFmpeg Video / MPEG-4 / H.264 / AAC.'
    bpy.ops.wm.save_as_mainfile(filepath=OUTPUT)
    print('Saved', OUTPUT)


if __name__ == '__main__':
    main()
