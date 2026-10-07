import bpy
arm=bpy.data.get if False else bpy.data.objects.get('Chris_Rig')
mesh=next((o for o in bpy.data.objects if o.name.startswith('Chris_Rigged_Body')),None)
print('ARMATURE',arm.name if arm else None)
print('SHAPE_KEYS', [k.name for k in mesh.data.shape_keys.key_blocks] if mesh and mesh.data.shape_keys else [])
print('FACE_BONES',[b.name for b in arm.pose.bones if any(x in b.name.lower() for x in ('jaw','lip','face','eye','brow','mouth'))] if arm else [])
