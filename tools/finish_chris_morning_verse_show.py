import bpy
from mathutils import Vector

OUTPUT = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\DB_Christian_StudyV13_Morning_Verse_Show.blend"


def aim(camera, target):
    camera.rotation_euler = (Vector(target) - camera.location).to_track_quat('-Z', 'Y').to_euler()


def camera(name, location, target, lens):
    data = bpy.data.cameras.get(name) or bpy.data.cameras.new(name)
    obj = bpy.data.objects.get(name)
    if not obj:
        obj = bpy.data.objects.new(name, data)
        bpy.context.scene.collection.objects.link(obj)
    obj.location = location
    data.lens = lens
    aim(obj, target)
    return obj


def main():
    scene = bpy.context.scene
    scene.render.engine = 'BLENDER_EEVEE'
    scene.render.resolution_x = 1920
    scene.render.resolution_y = 1080
    scene.render.resolution_percentage = 100
    scene.render.fps = 24
    scene.frame_start = 1
    scene.frame_end = 720

    # A short establishing shot introduces the reading, then Chris is close
    # enough for the eventual viseme animation to be visible.
    wide = camera('Chris_Morning_Wide_Camera', (0.42, -1.80, 1.68), (0.85, 2.34, 1.20), 36)
    close = camera('Chris_Morning_Close_Camera', (-0.10, -0.20, 1.25), (-0.10, 2.15, 0.64), 52)
    scene.camera = wide
    scene.timeline_markers.clear()
    marker = scene.timeline_markers.new('Morning verse opening', frame=1)
    marker.camera = wide
    marker = scene.timeline_markers.new('Chris recites today\'s verse', frame=97)
    marker.camera = close

    scene['show_title'] = 'Chris · Morning Verse'
    scene['show_format'] = '4-second establishing shot, then Chris recites the Daily Bible Verse'
    scene['audio_format'] = 'Ryan · ElevenLabs · 48 kHz WAV preferred'
    scene['delivery'] = 'Chris seated at his desk, speaking directly to the viewer'
    scene['lipsync'] = 'Mouth keys are ready; automated phoneme timing will be added with a Rhubarb mapping pass.'
    bpy.ops.wm.save_as_mainfile(filepath=OUTPUT)
    print('Saved', OUTPUT)


if __name__ == '__main__':
    main()
