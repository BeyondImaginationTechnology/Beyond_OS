"""Build initial, editable viseme shape keys for the Chris presenter mesh."""
import bpy
from mathutils import Vector

OUTPUT = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\DB_Christian_StudyV11_Chris_Facial_Keys.blend"
MESH_NAME = "Chris_Rigged_Body"


def ensure_key(mesh, name):
    key = mesh.data.shape_keys
    if key:
        existing = key.key_blocks.get(name)
        if existing:
            return existing
    return mesh.shape_key_add(name=name, from_mix=False)


def main():
    mesh = bpy.data.objects[MESH_NAME]
    bpy.context.view_layer.objects.active = mesh
    mesh.select_set(True)

    # Chris faces toward the presenter camera (negative Y).  The region is
    # deliberately small, leaving eyes, nose, hair, and the body untouched.
    basis = ensure_key(mesh, 'Basis')
    origin = Vector((-0.10, 1.955, 0.855))
    mouth_indices = []
    for vertex in mesh.data.vertices:
        point = mesh.matrix_world @ vertex.co
        if abs(point.x - origin.x) <= 0.145 and abs(point.z - origin.z) <= 0.095 and point.y <= 2.025:
            mouth_indices.append(vertex.index)

    if len(mouth_indices) < 12:
        raise RuntimeError('Could not identify enough mouth vertices to build Chris facial keys.')

    group = mesh.vertex_groups.get('Face_Mouth_Auto') or mesh.vertex_groups.new(name='Face_Mouth_Auto')
    group.add(mouth_indices, 1.0, 'REPLACE')

    def ratio(point):
        return max(0.0, 1.0 - abs(point.x - origin.x) / 0.145) * max(0.0, 1.0 - abs(point.z - origin.z) / 0.095)

    def make(name, transform):
        key = ensure_key(mesh, name)
        for index in mouth_indices:
            source = basis.data[index].co.copy()
            world = mesh.matrix_world @ source
            weight = ratio(world)
            key.data[index].co = transform(source, world, weight)
        key.value = 0.0
        key.slider_max = 1.0

    # These are restrained starting poses, suitable for keyframing or for a
    # later Rhubarb/phoneme mapping pass. They are intentionally mild so they
    # can be refined by hand without distorting Chris's stylized face.
    make('Mouth_Open', lambda co, p, w: co + Vector((0, -0.012 * w, -0.038 * w * (0.55 + max(0.0, (origin.z - p.z) / 0.095)))))
    make('Mouth_MBP', lambda co, p, w: co + Vector((0, 0.003 * w, 0.004 * w * (1.0 if p.z < origin.z else -0.35))))
    make('Mouth_FV', lambda co, p, w: co + Vector((0, -0.016 * w, 0.006 * w)))
    make('Mouth_EE', lambda co, p, w: co + Vector((0.027 * w * (1.0 if p.x >= origin.x else -1.0), -0.004 * w, 0)))
    make('Mouth_OH', lambda co, p, w: co + Vector((-0.032 * w * (1.0 if p.x >= origin.x else -1.0), -0.018 * w, 0.010 * w)))
    make('Mouth_AA', lambda co, p, w: co + Vector((0, -0.016 * w, -0.055 * w * (0.4 + max(0.0, (origin.z - p.z) / 0.095)))))

    mesh['facial_viseme_keys'] = 'Mouth_Open,Mouth_MBP,Mouth_FV,Mouth_EE,Mouth_OH,Mouth_AA'
    mesh['mouth_vertex_group'] = 'Face_Mouth_Auto'
    mesh['facial_animation_status'] = 'Initial editable viseme shapes; refine manually after WAV import.'
    bpy.context.scene['chris_lipsync_workflow'] = 'Import Ryan WAV at 24 fps, key these shape keys, then map Rhubarb phonemes if used.'
    bpy.ops.wm.save_as_mainfile(filepath=OUTPUT)
    print('Saved', OUTPUT, 'with', len(mouth_indices), 'mouth vertices.')


if __name__ == '__main__':
    main()
