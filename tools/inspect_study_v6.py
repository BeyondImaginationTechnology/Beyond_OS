import bpy
for o in bpy.context.scene.objects:
    if any(k in o.name.lower() for k in ('chris','desk','table','shelf','armature','tripo')):
        print(o.name, o.type, tuple(round(v,3) for v in o.location), tuple(round(v,3) for v in o.dimensions))
