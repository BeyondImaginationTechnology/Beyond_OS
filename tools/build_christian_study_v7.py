import bpy
from mathutils import Vector
fbx = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\imports\chris_rigged\tripo_convert_6f7facd5-efbd-4926-8914-fd78c7b4ec0f.fbx"
out = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\DB_Christian_StudyV7_Chris_Rigged.blend"
old = bpy.data.objects.get("Chris_Root")
if old:
    bpy.data.objects.remove(old, do_unlink=True)
bpy.ops.object.select_all(action='DESELECT')
bpy.ops.import_scene.fbx(filepath=fbx)
imported = list(bpy.context.selected_objects)
armature = next((o for o in imported if o.type == 'ARMATURE'), None)
meshes = [o for o in imported if o.type == 'MESH']
if not armature or not meshes:
    raise RuntimeError('Expected armature and mesh in imported FBX')
armature.name = 'Chris_Rig'
armature.location = (-0.1, 1.32, 0.025)
armature.scale = (1.62, 1.62, 1.62)
for mesh in meshes:
    mesh.name = 'Chris_Rigged_Body'
    if mesh.parent is None:
        mesh.parent = armature
# stable studio grouping
coll = bpy.data.collections.get('Chris_Rigged') or bpy.data.collections.new('Chris_Rigged')
if coll.name not in bpy.context.scene.collection.children:
    bpy.context.scene.collection.children.link(coll)
for obj in [armature] + meshes:
    for c in list(obj.users_collection):
        c.objects.unlink(obj)
    coll.objects.link(obj)
# retain existing camera framing and mark asset intent
armature['dailybreath_role'] = 'chris_presenter'
armature['rig_source'] = 'Tripo Mixamo humanoid'
armature['ready_for_animation'] = True
bpy.context.view_layer.objects.active = armature
armature.select_set(True)
bpy.ops.wm.save_as_mainfile(filepath=out)
print('SAVED', out)
print('CHR_RIG_LOC', tuple(round(v,3) for v in armature.location))
print('CHR_RIG_SCALE', tuple(round(v,3) for v in armature.scale))
