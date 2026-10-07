import bpy
for o in bpy.context.scene.objects:
 if 'chair' in o.name.lower() or 'desk' in o.name.lower(): print(o.name,o.type,tuple(round(v,3) for v in o.location),tuple(round(v,3) for v in o.rotation_euler),tuple(round(v,3) for v in o.dimensions))
